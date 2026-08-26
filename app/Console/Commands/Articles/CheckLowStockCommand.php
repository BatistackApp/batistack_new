<?php

namespace App\Console\Commands\Articles;

use App\Enums\Commerce\OrderStatus;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\PurchaseOrderItem;
use App\Models\User;
use App\Notifications\Articles\LowStockNotification;
use App\Notifications\Articles\RestockOrdersGeneratedNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CheckLowStockCommand extends Command
{
    protected $signature = 'articles:check-stocks';

    protected $description = 'Vérifie les articles sous le seuil de sécurité, notifie, et génère des commandes de réapprovisionnement automatiques.';

    public function handle(): int
    {
        $this->info('Analyse des stocks en cours...');

        // 1. Alertes par Entrepôt (existant)
        $this->checkWarehouseAlerts();

        // 2. Génération des Commandes Fournisseurs (Nouveau - Issue 119)
        $this->generateRestockOrders();

        return CommandAlias::SUCCESS;
    }

    protected function checkWarehouseAlerts(): void
    {
        $criticalStocks = Stock::whereColumn('quantity', '<=', 'min_threshold')
            ->where('min_threshold', '>', 0)
            ->with(['item', 'warehouse'])
            ->get();

        if ($criticalStocks->isEmpty()) {
            $this->info('Aucun stock critique détecté par entrepôt.');

            return;
        }

        $this->warn("{$criticalStocks->count()} alertes d'entrepôt détectées.");

        $users = User::admin()->get(); // À filtrer par rôle/permission

        foreach ($criticalStocks as $stock) {
            Notification::send($users, new LowStockNotification($stock));
            $this->line("- Alerte : {$stock->item->name} dans {$stock->warehouse->name}");
        }

        $this->info('Notifications par entrepôt envoyées.');
    }

    protected function generateRestockOrders(): void
    {
        $this->info('Analyse globale pour le réapprovisionnement automatique...');

        $items = Item::where('is_active', true)
            ->where('min_stock', '>', 0)
            ->get();

        $shortagesBySupplier = [];

        foreach ($items as $item) {
            // Stock physique total de l'article sur tous les entrepôts
            $physicalStock = Stock::where('item_id', $item->id)->sum('quantity');

            // Stock en commande (brouillon, confirmé, livraison partielle)
            $pendingStock = PurchaseOrderItem::where('item_id', $item->id)
                ->whereHas('order', function ($q) {
                    $q->whereIn('status', [
                        OrderStatus::DRAFT,
                        OrderStatus::CONFIRMED,
                        OrderStatus::PARTIALLY_DELIVERED,
                    ]);
                })
                ->sum('quantity');

            // Stock Disponible = Stock Physique + En commande
            $availableStock = $physicalStock + $pendingStock;

            if ($availableStock < $item->min_stock) {
                // On calcule le déficit pour revenir au moins au min_stock
                $shortage = $item->min_stock - $availableStock;

                $supplierId = $item->supplier_id;
                $supplierKey = $supplierId ?? 'no_supplier';

                if (! isset($shortagesBySupplier[$supplierKey])) {
                    $shortagesBySupplier[$supplierKey] = [];
                }

                $shortagesBySupplier[$supplierKey][] = [
                    'item' => $item,
                    'shortage' => $shortage,
                ];
            }
        }

        if (empty($shortagesBySupplier)) {
            $this->info('Aucun réapprovisionnement automatique nécessaire.');

            return;
        }

        $generatedPoCount = 0;

        foreach ($shortagesBySupplier as $supplierKey => $missingItems) {
            if ($supplierKey === 'no_supplier') {
                $this->warn('Articles en rupture sans fournisseur défini ignorés ('.count($missingItems).' articles).');

                continue;
            }

            // Chercher s'il y a déjà un PO brouillon auto pour ce fournisseur aujourd'hui
            $po = PurchaseOrder::firstOrCreate(
                [
                    'supplier_id' => $supplierKey,
                    'status' => OrderStatus::DRAFT,
                    'reference' => 'PO-RESTOCK-'.date('Ymd').'-'.$supplierKey,
                ],
                [
                    'ordered_at' => now(),
                    'expected_delivery_date' => now()->addDays(5),
                    'total_ht' => 0,
                    'total_ttc' => 0,
                ]
            );

            foreach ($missingItems as $missing) {
                $item = $missing['item'];
                $qty = $missing['shortage'];

                $poItem = PurchaseOrderItem::firstOrNew([
                    'purchase_order_id' => $po->id,
                    'item_id' => $item->id,
                ]);

                $poItem->name = $item->name;
                $poItem->quantity = $qty; // Remplace la quantité pour être sûr d'avoir le compte exact
                $poItem->price_unit_ht = $item->purchase_price;
                $poItem->vat_rate_id = $item->vat_rate_id;
                $poItem->save();
            }

            $this->recalculatePoTotals($po);
            $generatedPoCount++;
            $this->info("Commande Brouillon générée pour le fournisseur ID {$supplierKey} (PO: {$po->reference})");
        }

        if ($generatedPoCount > 0) {
            $this->info("{$generatedPoCount} commandes de réapprovisionnement générées.");
            $users = User::admin()->get();
            Notification::send($users, new RestockOrdersGeneratedNotification($generatedPoCount));
        }
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
