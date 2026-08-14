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

            // Exclut le bon de commande brouillon déjà généré pour cette maquette + ce fournisseur,
            // afin qu'il puisse être mis à jour sans être compté comme un besoin déjà couvert.
            $targetReference = $this->orderReference($bimModel, $item->supplier_id);

            $pendingOrderStock = (float) PurchaseOrderItem::where('item_id', $item->id)
                ->whereHas('order', function ($query) use ($targetReference) {
                    $query->whereIn('status', [
                        OrderStatus::DRAFT,
                        OrderStatus::CONFIRMED,
                        OrderStatus::PARTIALLY_DELIVERED,
                    ])->where('reference', '!=', $targetReference);
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
     *
     * @return array{purchase_orders: array<int, PurchaseOrder>, ignored_items: array<int, Item>}
     */
    public function generatePurchaseOrders(BimModel $bimModel): array
    {
        $requirements = $this->resolveRequirements($bimModel);
        $chantierId = $this->resolveChantierId($bimModel);

        $bySupplier = [];
        $ignoredItems = [];

        foreach ($requirements as $requirement) {
            $item = $requirement['item'];

            if (! $item->supplier_id) {
                Log::warning('BIM: Article en rupture sans fournisseur défini, ignoré.', ['item_id' => $item->id]);
                $ignoredItems[] = $item;

                continue;
            }

            $bySupplier[$item->supplier_id][] = $requirement;
        }

        $purchaseOrders = [];

        foreach ($bySupplier as $supplierId => $missingItems) {
            $reference = $this->orderReference($bimModel, $supplierId);

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

            $requiredItemIds = [];

            foreach ($missingItems as $missing) {
                $item = $missing['item'];
                $qty = $missing['quantity_to_order'];

                $requiredItemIds[] = $item->id;

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

            // Synchronise les lignes : supprime celles qui ne sont plus nécessaires.
            $po->items()->whereNotIn('item_id', $requiredItemIds)->delete();

            $this->recalculatePoTotals($po);

            $purchaseOrders[] = $po;

            Log::info("BIM: Bon de commande brouillon généré/mis à jour pour le fournisseur {$supplierId}", ['po_id' => $po->id]);
        }

        return [
            'purchase_orders' => $purchaseOrders,
            'ignored_items' => $ignoredItems,
        ];
    }

    protected function orderReference(BimModel $bimModel, ?int $supplierId): string
    {
        return 'PO-BIM-'.$bimModel->id.'-'.$supplierId;
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
