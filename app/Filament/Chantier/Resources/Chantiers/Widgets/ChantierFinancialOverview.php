<?php

namespace App\Filament\Chantier\Resources\Chantiers\Widgets;

use App\Models\Chantiers\Chantier;
use App\Services\Chantiers\ChantierAnalyticService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use Illuminate\Database\Eloquent\Model;

class ChantierFinancialOverview extends StatsOverviewWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (! $this->record instanceof Chantier) {
            return [];
        }

        $analyticService = app(ChantierAnalyticService::class);
        $metrics = $analyticService->getPerformanceMetrics($this->record);
        $financials = $metrics['financials'];

        $marginReal = $financials['margin_real'];
        $marginPercent = $financials['margin_percent'];
        $budget = $financials['budget_ht'];
        $totalCost = $financials['total_cost_real'];

        $marginColor = 'gray';
        if ($marginPercent > 15) {
            $marginColor = 'success';
        } elseif ($marginPercent > 0) {
            $marginColor = 'warning';
        } elseif ($marginPercent < 0) {
            $marginColor = 'danger';
        }

        $descriptionCosts = 'Main d\'œuvre';
        if ($financials['material_cost_real'] > 0) {
            $descriptionCosts .= ' + Matériaux';
        }
        if ($financials['fleet_cost_real'] > 0) {
            $descriptionCosts .= ' + Flotte';
        }

        return [
            Stat::make('Budget Vendu (HT)', number_format($budget, 2, ',', ' ') . ' €')
                ->description('Enveloppe globale du projet')
                ->descriptionIcon(Phosphor::CurrencyEur)
                ->color('primary'),

            Stat::make('Coûts Engagés (HT)', number_format($totalCost, 2, ',', ' ') . ' €')
                ->description($descriptionCosts)
                ->descriptionIcon(Phosphor::Wrench)
                ->color('warning'),

            Stat::make('Marge Réelle', number_format($marginReal, 2, ',', ' ') . ' €')
                ->description(number_format($marginPercent, 1, ',', ' ') . ' % de marge nette')
                ->descriptionIcon($marginReal >= 0 ? Phosphor::TrendUp : Phosphor::TrendDown)
                ->color($marginColor),
        ];
    }
}
