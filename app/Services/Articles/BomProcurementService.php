<?php

namespace App\Services\Articles;

use App\Enums\Commerce\OrderStatus;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\PurchaseOrderItem;
use App\Models\Vision3D\BimModel;
use App\Models\Vision3D\BimQuantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BomProcurementService
{
    /**
     * Calcule le besoin net (besoin brut - stock physique - stock en commande) pour chaque article
     * référencé dans les quantitatifs d'une maquette.
     *
     * @return array<int, array{item: Item, quantity_required: float, physical_stock: float, pending_order_stock: float, quantity_to_order: float}>
     */
    public function resolveRequirements(BimModel $bimModel): array
    {
        $requirements = BimQuantity::query()
            ->where('bim_model_id', $bimModel->id)
            ->select('item_id', DB::raw('SUM(quantity_required) as total_required'))
            ->groupBy('item_id')
            ->get();

        $resolved = [];

        foreach ($requirements as $req) {
            $item = Item::with('supplier')->find($req->item_id);
            if (! $item) {
                continue;
            }

            $physicalStock = (float) Stock::where('item_id', $item->id)->sum('quantity');

            $pendingOrderStock = (float) PurchaseOrderItem::where('item_id', $item->id)
                ->whereHas('order', function ($query) {
                    $query->whereIn('status', [
                        OrderStatus::DRAFT,
                        OrderStatus::CONFIRMED,
                        OrderStatus::PARTIALLY_DELIVERED,
                    ]);
                })
                ->sum('quantity');

            $quantityToOrder = max(0, $req->total_required - $physicalStock - $pendingOrderStock);

            if ($quantityToOrder > 0) {
                $resolved[] = [
                    'item' => $item,
                    'quantity_required' => (float) $req->total_required,
                    'physical_stock' => $physicalStock,
                    'pending_order_stock' => $pendingOrderStock,
                    'quantity_to_order' => $quantityToOrder,
                ];
            }
        }

        return $resolved;
    }

    /**
     * Génère (ou met à jour) des bons de commande brouillons, groupés par fournisseur,
     * pour les articles en rupture par rapport aux quantitatifs de la maquette.
     */
    public function generatePurchaseOrders(BimModel $bimModel): array
    {
        $requirements = $this->resolveRequirements($bimModel);
        $chantierId = $this->resolveChantierId($bimModel);

        $bySupplier = [];

        foreach ($requirements as $requirement) {
            $item = $requirement['item'];

            if (! $item->supplier_id) {
                Log::warning('BIM: Article en rupture sans fournisseur défini, ignoré.', ['item_id' => $item->id]);

                continue;
            }

            $bySupplier[$item->supplier_id][] = $requirement;
        }

        $purchaseOrders = [];

        foreach ($bySupplier as $supplierId => $missingItems) {
            $reference = 'PO-BIM-'.date('Ymd').'-'.$supplierId;

            $po = PurchaseOrder::updateOrCreate(
                [
                    'supplier_id' => $supplierId,
                    'status' => OrderStatus::DRAFT,
                    'reference' => $reference,
                ],
                [
                    'chantier_id' => $chantierId,
                    'ordered_at' => now(),
                    'expected_delivery_date' => now()->addDays(5),
                ]
            );

            foreach ($missingItems as $missing) {
                $item = $missing['item'];
                $qty = $missing['quantity_to_order'];

                $poItem = PurchaseOrderItem::firstOrNew([
                    'purchase_order_id' => $po->id,
                    'item_id' => $item->id,
                ]);

                $poItem->name = $item->name;
                $poItem->quantity = $qty;
                $poItem->price_unit_ht = $item->purchase_price;
                $poItem->vat_rate_id = $item->vat_rate_id;
                $poItem->save();
            }

            $this->recalculatePoTotals($po);

            $purchaseOrders[] = $po;

            Log::info("BIM: Bon de commande brouillon généré/mis à jour pour le fournisseur {$supplierId}", ['po_id' => $po->id]);
        }

        return $purchaseOrders;
    }

    protected function resolveChantierId(BimModel $bimModel): ?int
    {
        $modelable = $bimModel->modelable;

        return $modelable instanceof Chantier ? $modelable->id : null;
    }

    protected function recalculatePoTotals(PurchaseOrder $po): void
    {
        $totalHt = 0;
        $totalTtc = 0;

        foreach ($po->items()->with('vatRate')->get() as $item) {
            $lineHt = $item->quantity * $item->price_unit_ht;
            $lineTtc = $lineHt * (1 + ($item->vatRate?->rate ?? 0) / 100);

            $totalHt += $lineHt;
            $totalTtc += $lineTtc;
        }

        $po->update([
            'total_ht' => $totalHt,
            'total_ttc' => $totalTtc,
        ]);
    }
}
