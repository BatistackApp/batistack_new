<?php

namespace App\Filament\Chantier\Resources\Chantiers\Widgets;

use App\Enums\Chantiers\ChantierReserveStatus;
use App\Models\Chantiers\Chantier;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ReservesOverviewWidget extends StatsOverviewWidget
{
    public ?Model $record = null;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        if (! $this->record instanceof Chantier) {
            return [];
        }

        $open = $this->record->reserves()->where('status', ChantierReserveStatus::OPEN)->count();
        $inProgress = $this->record->reserves()->where('status', ChantierReserveStatus::IN_PROGRESS)->count();
        $lifted = $this->record->reserves()->where('status', ChantierReserveStatus::LIFTED)->count();
        $critical = $this->record->reserves()->where('status', '!=', ChantierReserveStatus::LIFTED->value)
            ->where('severity', 'critical')->count();

        return [
            Stat::make('Réserves ouvertes', $open)
                ->descriptionIcon(Phosphor::WarningCircle)
                ->color('danger'),
            Stat::make('En cours', $inProgress)
                ->descriptionIcon(Phosphor::HardHat)
                ->color('warning'),
            Stat::make('Levées', $lifted)
                ->descriptionIcon(Phosphor::CheckCircle)
                ->color('success'),
            Stat::make('Critiques à lever', $critical)
                ->descriptionIcon(Phosphor::Warning)
                ->color($critical > 0 ? 'danger' : 'gray'),
        ];
    }
}
