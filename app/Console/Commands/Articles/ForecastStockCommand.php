<?php

namespace App\Console\Commands\Articles;

use App\Enums\Commerce\OrderStatus;
use App\Models\Articles\Item;
use App\Models\Articles\StockForecast;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\PurchaseOrderItem;
use App\Models\User;
use App\Notifications\Articles\ForecastRuptureNotification;
use App\Services\Articles\StockForecastService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ForecastStockCommand extends Command
{
    protected $signature = 'articles:forecast-stock {--horizon=60 : Horizon de prévision en jours} {--force : Force la génération même si confiance low}';

    protected $description = 'Prévisions de ruptures de stock (historique + saisonnalité 24m + besoins BIM chantiers planifiés) et génération de commandes prédictives.';

    public function handle(StockForecastService $service): int
    {
        $horizon = (int) $this->option('horizon');
        $force = (bool) $this->option('force');

        $this->info("Prévision des ruptures (horizon {$horizon}j) en cours...");

        $items = Item::where('is_active', true)
            ->where('min_stock', '>', 0)
            ->with(['supplier'])
            ->get();

        if ($items->isEmpty()) {
            $this->info('Aucun article avec min_stock > 0.');

            return CommandAlias::SUCCESS;
        }

        $forecastsCount = 0;
        $shortagesBySupplier = [];
        $urgentCount = 0;

        foreach ($items as $item) {
            $forecast = $service->forecast($item, $horizon);

            // Persistance
            StockForecast::create([
                'item_id' => $item->id,
                'warehouse_id' => null,
                'forecasted_at' => now(),
                'days_until_rupture' => $forecast['days_until_rupture'],
                'daily_burn' => $forecast['daily_burn'],
                'seasonality_coeff' => $forecast['seasonality_coeff'],
                'planned_needs' => $forecast['planned_needs'],
                'available_stock' => $forecast['available_stock'],
                'suggested_qty' => $forecast['suggested_qty'],
                'suggested_order_date' => $forecast['suggested_order_date'],
                'confidence' => $forecast['confidence'],
            ]);

            $forecastsCount++;

            if ($forecast['suggested_qty'] <= 0) {
                continue;
            }

            if ($forecast['confidence'] === 'low' && ! $force && $forecast['daily_burn'] == 0) {
                // On a déjà fallback min_stock dans le service, mais on évite de générer si vraiment vide
                // sauf --force
            }

            // Délai fournisseur pour déterminer si commande prédictive nécessaire
            $delay = $item->supplier?->delivery_delay_days ?? 5;
            $daysUntilRupture = $forecast['days_until_rupture'];

            // On génère une commande prédictive si rupture avant horizon OU si quantité suggérée >0 et rupture <= delay+7
            $needsOrder = $forecast['suggested_qty'] > 0
                && $daysUntilRupture !== null
                && $daysUntilRupture <= ($horizon + $delay);

            if (! $needsOrder && $forecast['suggested_qty'] > 0 && $forecast['confidence'] === 'low') {
                // Fallback min_stock : on génère quand même si sous seuil
                $needsOrder = true;
            }

            if (! $needsOrder) {
                continue;
            }

            $supplierId = $item->supplier_id;
            $supplierKey = $supplierId ?? 'no_supplier';

            if (! isset($shortagesBySupplier[$supplierKey])) {
                $shortagesBySupplier[$supplierKey] = [];
            }

            $shortagesBySupplier[$supplierKey][] = [
                'item' => $item,
                'forecast' => $forecast,
            ];

            if ($daysUntilRupture !== null && $daysUntilRupture <= 14) {
                $urgentCount++;
            }
        }

        $this->info("{$forecastsCount} prévisions enregistrées.");

        if (empty($shortagesBySupplier)) {
            $this->info('Aucune commande prédictive nécessaire.');

            return CommandAlias::SUCCESS;
        }

        $generatedPoCount = 0;

        foreach ($shortagesBySupplier as $supplierKey => $entries) {
            if ($supplierKey === 'no_supplier') {
                $this->warn('Articles en rupture prédite sans fournisseur ignorés ('.count($entries).' articles).');

                continue;
            }

            $reference = 'PO-FORECAST-'.now()->format('Ymd').'-'.$supplierKey;

            $po = PurchaseOrder::firstOrCreate(
                [
                    'supplier_id' => $supplierKey,
                    'status' => OrderStatus::DRAFT,
                    'reference' => $reference,
                ],
                [
                    'ordered_at' => now(),
                    'expected_delivery_date' => now()->addDays(7),
                    'total_ht' => 0,
                    'total_ttc' => 0,
                ]
            );

            foreach ($entries as $entry) {
                $item = $entry['item'];
                $qty = $entry['forecast']['suggested_qty'];

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
            $generatedPoCount++;
            $this->info("Commande prédictive générée pour fournisseur ID {$supplierKey} (PO: {$po->reference})");
        }

        if ($generatedPoCount > 0) {
            $this->info("{$generatedPoCount} commandes prédictives générées ({$urgentCount} urgentes <=14j).");
            $users = User::admin()->get();
            if ($users->isNotEmpty()) {
                Notification::send($users, new ForecastRuptureNotification($generatedPoCount, $urgentCount));
            }
        }

        return CommandAlias::SUCCESS;
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
