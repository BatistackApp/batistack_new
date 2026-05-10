<?php

namespace App\Filament\Widgets;

use App\Models\Core\Company;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class CoreStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $companySet = Company::exists();
        $activeUnits = Unit::where('is_active', true)->count();
        $defaultVat = VatRate::where('is_default', true)->first();

        return [
            Stat::make('Configuration Entreprise', $companySet ? 'Complétée' : 'À renseigner')
                ->description($companySet ? 'Identité visuelle et légale active' : 'Action requise : Paramétrez votre société')
                ->descriptionIcon($companySet ? Phosphor::CheckCircle : Phosphor::WarningCircle)
                ->color($companySet ? 'success' : 'danger'),

            Stat::make('Unités Actives', $activeUnits)
                ->description('Unités de mesure disponibles dans le catalogue')
                ->descriptionIcon(Phosphor::Scales)
                ->color('info'),

            Stat::make('Taux de TVA par défaut', number_format($defaultVat?->rate, 2).'%')
                ->description($defaultVat?->name ?? 'Veuillez définir un taux par défaut')
                ->descriptionIcon(Phosphor::Percent)
                ->color($defaultVat ? 'primary' : 'warning'),
        ];
    }
}
