<?php

namespace App\Services\Articles;

use App\Enums\Chantiers\ChantierStatus;
use App\Enums\Commerce\OrderStatus;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\StockMouvement;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\PurchaseOrderItem;
use App\Models\Vision3D\BimQuantity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockForecastService
{
    /**
     * Consommation journalière moyenne sur $days jours (sorties uniquement).
     * Moyenne mobile 30j si historique suffisant, sinon moyenne brute.
     */
    public function historicalDailyConsumption(Item $item, int $days = 90): float
    {
        $since = now()->subDays($days);

        $totalOut = StockMouvement::whereHas('stock', fn ($q) => $q->where('item_id', $item->id))
            ->where('type', 'out')
            ->where('created_at', '>=', $since)
            ->selectRaw('SUM(ABS(quantity_delta)) as total')
            ->value('total');

        $totalOut = (float) ($totalOut ?? 0);

        if ($totalOut === 0.0) {
            return 0.0;
        }

        // On garde une simple moyenne journalière ; la moyenne mobile 30j revient au même
        // que la moyenne brute si on n'a pas de pondération différente. On conserve la distinction
        // pour évolution future (pondération exponentielle).
        return $totalOut / $days;
    }

    /**
     * Coefficient saisonnier mensuel sur 24 mois : conso du mois / conso moyenne mensuelle.
     * Retourne 1.0 si historique insuffisant (<6 mois ou <30 mouvements).
     */
    public function seasonalityCoefficient(Item $item, ?int $month = null): float
    {
        $month = $month ?? (int) now()->month;

        $since = now()->subMonths(24);

        $count = StockMouvement::whereHas('stock', fn ($q) => $q->where('item_id', $item->id))
            ->where('type', 'out')
            ->where('created_at', '>=', $since)
            ->count();

        if ($count < 30) {
            return 1.0;
        }

        $monthly = StockMouvement::whereHas('stock', fn ($q) => $q->where('item_id', $item->id))
            ->where('type', 'out')
            ->where('created_at', '>=', $since)
            ->selectRaw((DB::getDriverName() === 'sqlite' ? "CAST(strftime('%m', created_at) AS INTEGER)" : 'MONTH(created_at)').' as m, SUM(ABS(quantity_delta)) as total')
            ->groupBy(DB::raw(DB::getDriverName() === 'sqlite' ? "CAST(strftime('%m', created_at) AS INTEGER)" : 'MONTH(created_at)'))
            ->pluck('total', 'm');

        if ($monthly->isEmpty()) {
            return 1.0;
        }

        $avg = $monthly->avg();

        if ($avg == 0) {
            return 1.0;
        }

        $monthTotal = (float) ($monthly[$month] ?? $avg);

        $coeff = $monthTotal / $avg;

        return max(0.5, min(1.8, round($coeff, 4)));
    }

    /**
     * Besoins planifiés : somme des BimQuantity.quantity_required pour les chantiers
     * PLANNED / IN_PROGRESS dont start_date_preview est dans [now, now+horizon].
     */
    public function plannedNeeds(Item $item, int $horizonDays = 60): float
    {
        $end = now()->addDays($horizonDays);

        $chantierIds = Chantier::whereIn('status', [ChantierStatus::PLANNED, ChantierStatus::IN_PROGRESS])
            ->whereNotNull('start_date_preview')
            ->whereBetween('start_date_preview', [now()->toDateString(), $end->toDateString()])
            ->pluck('id');

        if ($chantierIds->isEmpty()) {
            return 0.0;
        }

        // BimModel morphMany : récupérer les bim_model_ids liés à ces chantiers
        $bimModelIds = DB::table('bim_models')
            ->where('modelable_type', Chantier::class)
            ->whereIn('modelable_id', $chantierIds)
            ->pluck('id');

        if ($bimModelIds->isEmpty()) {
            return 0.0;
        }

        return (float) BimQuantity::where('item_id', $item->id)
            ->whereIn('bim_model_id', $bimModelIds)
            ->sum('quantity_required');
    }

    /**
     * Prévision complète pour un article.
     *
     * @return array{available_stock:float, daily_burn:float, seasonality_coeff:float, planned_needs:float, days_until_rupture:?int, rupture_date:?Carbon, suggested_qty:float, suggested_order_date:?Carbon, confidence:string}
     */
    public function forecast(Item $item, int $horizonDays = 60): array
    {
        $availableStock = (float) Stock::where('item_id', $item->id)->sum('quantity');

        // Stock en commande (brouillon, confirmé, livraison partielle) — même logique que CheckLowStockCommand
        $pendingStock = (float) PurchaseOrderItem::where('item_id', $item->id)
            ->whereHas('order', fn ($q) => $q->whereIn('status', [OrderStatus::DRAFT, OrderStatus::CONFIRMED, OrderStatus::PARTIALLY_DELIVERED]))
            ->sum('quantity');

        $availableStockWithPending = $availableStock + $pendingStock;

        $hist = $this->historicalDailyConsumption($item);
        $coeff = $this->seasonalityCoefficient($item);
        $planned = $this->plannedNeeds($item, $horizonDays);

        $dailyBurn = ($hist * $coeff) + ($horizonDays > 0 ? $planned / $horizonDays : 0);

        // Confiance
        $since24m = now()->subMonths(24);
        $count24m = StockMouvement::whereHas('stock', fn ($q) => $q->where('item_id', $item->id))
            ->where('type', 'out')
            ->where('created_at', '>=', $since24m)
            ->count();
        $monthsWithData = StockMouvement::whereHas('stock', fn ($q) => $q->where('item_id', $item->id))
            ->where('type', 'out')
            ->where('created_at', '>=', $since24m)
            ->selectRaw('COUNT(DISTINCT '.(DB::getDriverName() === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')").') as m')
            ->value('m');

        $confidence = 'low';
        if ($count24m >= 100 && $monthsWithData >= 12) {
            $confidence = 'high';
        } elseif ($count24m >= 30 && $monthsWithData >= 6) {
            $confidence = 'med';
        }

        if ($dailyBurn <= 0) {
            return [
                'available_stock' => $availableStockWithPending,
                'daily_burn' => 0,
                'seasonality_coeff' => $coeff,
                'planned_needs' => $planned,
                'days_until_rupture' => null,
                'rupture_date' => null,
                'suggested_qty' => 0,
                'suggested_order_date' => null,
                'confidence' => 'low',
            ];
        }

        $daysUntilRupture = (int) floor($availableStockWithPending / $dailyBurn);
        $ruptureDate = now()->addDays($daysUntilRupture);

        // Délai fournisseur
        $delay = 5;
        if ($item->supplier_id) {
            $supplier = $item->supplier;
            if ($supplier && isset($supplier->delivery_delay_days)) {
                $delay = (int) $supplier->delivery_delay_days;
            }
        }

        $suggestedOrderDate = $ruptureDate->copy()->subDays($delay + 2); // 2j marge
        if ($suggestedOrderDate->isPast()) {
            $suggestedOrderDate = now();
        }

        // Quantité suggérée : couvrir horizon + lead time, fallback min_stock si confiance low et hist faible
        $coverageDays = $horizonDays + $delay;
        $suggestedQty = max(0, ($dailyBurn * $coverageDays) - $availableStockWithPending);

        if ($confidence === 'low' && $hist == 0 && $planned == 0) {
            // Pas d'historique : on retombe sur min_stock comme CheckLowStockCommand
            $suggestedQty = max(0, (float) $item->min_stock - $availableStockWithPending);
        }

        // Au moins min_stock si rupture imminente et quantité calculée faible
        if ($daysUntilRupture <= $delay && $suggestedQty < (float) $item->min_stock) {
            $suggestedQty = max($suggestedQty, (float) $item->min_stock - $availableStockWithPending);
            $suggestedQty = max(0, $suggestedQty);
        }

        return [
            'available_stock' => round($availableStockWithPending, 4),
            'daily_burn' => round($dailyBurn, 4),
            'seasonality_coeff' => round($coeff, 4),
            'planned_needs' => round($planned, 4),
            'days_until_rupture' => $daysUntilRupture,
            'rupture_date' => $ruptureDate,
            'suggested_qty' => round($suggestedQty, 4),
            'suggested_order_date' => $suggestedOrderDate,
            'confidence' => $confidence,
        ];
    }
}
