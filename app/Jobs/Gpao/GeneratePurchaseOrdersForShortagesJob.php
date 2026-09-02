<?php

namespace App\Jobs\Gpao;

use App\Enums\Commerce\OrderStatus;
use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\PurchaseOrderItem;
use App\Models\Gpao\ManufacturingRequirement;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GeneratePurchaseOrdersForShortagesJob implements ShouldBeUnique, ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function uniqueId(): string
    {
        return 'mrp_shortage_scan';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting MRP Shortage Analysis...');

        // 1. Identifier tous les besoins non satisfaits pour les OF en cours / planifiés
        $requirements = ManufacturingRequirement::whereHas('manufacturingOrder', function ($query) {
            $query->whereIn('status', [
                ManufacturingStatus::DRAFT,
                ManufacturingStatus::PLANNED,
                ManufacturingStatus::IN_PROGRESS,
            ]);
        })
            ->select('item_id', DB::raw('SUM(quantity_required - quantity_consumed) as total_needed'))
            ->groupBy('item_id')
            ->having('total_needed', '>', 0)
            ->get();

        $shortagesBySupplier = [];

        foreach ($requirements as $req) {
            $item = Item::with('supplier')->find($req->item_id);
            if (! $item) {
                continue;
            }

            // 2. Calculer le stock physique
            $physicalStock = Stock::where('item_id', $item->id)->sum('quantity');

            // 3. Calculer le stock en commande (attendu)
            $pendingOrderStock = PurchaseOrderItem::where('item_id', $item->id)
                ->whereHas('order', function ($q) {
                    $q->whereIn('status', [
                        OrderStatus::DRAFT,
                        OrderStatus::CONFIRMED,
                        OrderStatus::PARTIALLY_DELIVERED,
                    ]);
                })
                ->sum('quantity');

            $availableStock = $physicalStock + $pendingOrderStock;
            $shortage = $req->total_needed - $availableStock;

            if ($shortage > 0) {
                $supplierId = $item->supplier_id;
                $supplierKey = $supplierId ?? 'no_supplier';

                if (! isset($shortagesBySupplier[$supplierKey])) {
                    $shortagesBySupplier[$supplierKey] = [];
                }

                $shortagesBySupplier[$supplierKey][] = [
                    'item' => $item,
                    'quantity_missing' => $shortage,
                ];
            }
        }

        // 4. Générer les Purchase Orders (Brouillons)
        foreach ($shortagesBySupplier as $supplierKey => $missingItems) {
            if ($supplierKey === 'no_supplier') {
                Log::warning('MRP: Articles en rupture sans fournisseur défini ('.count($missingItems).' articles). Impossible de générer la commande auto.');

                continue;
            }

            // Chercher s'il y a déjà un PO brouillon auto pour ce fournisseur aujourd'hui
            $po = PurchaseOrder::firstOrCreate(
                [
                    'supplier_id' => $supplierKey,
                    'status' => OrderStatus::DRAFT,
                    'reference' => 'PO-AUTO-'.date('Ymd').'-'.$supplierKey,
                    'ordered_at' => now(),
                    'expected_delivery_date' => now()->addDays(5),
                ],
                [
                    'total_ht' => 0,
                    'total_ttc' => 0,
                ]
            );

            foreach ($missingItems as $missing) {
                $item = $missing['item'];
                $qty = $missing['quantity_missing'];

                // Chercher si l'article est déjà dans ce PO
                $poItem = PurchaseOrderItem::firstOrNew([
                    'purchase_order_id' => $po->id,
                    'item_id' => $item->id,
                ]);

                // On met à jour la quantité pour refléter la rupture exacte calculée
                $poItem->name = $item->name;
                $poItem->quantity = $qty;
                $poItem->price_unit_ht = $item->purchase_price;
                $poItem->vat_rate_id = $item->vat_rate_id;
                $poItem->save();
            }

            // Mettre à jour les totaux du PO
            $this->recalculatePoTotals($po);

            Log::info("MRP: Purchase Order Brouillon généré/mis à jour pour le fournisseur {$supplierKey}", ['po_id' => $po->id]);
        }
    }

    protected function recalculatePoTotals(PurchaseOrder $po)
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
