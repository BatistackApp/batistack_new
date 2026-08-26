<?php

namespace App\Filament\Flottes\Widgets;

use App\Models\Flottes\VehicleContract;
use LaBoiteACode\FilamentDashboardWidgets\Data\UsageLimit;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\UsageLimitsWidget;

class LeasingUsageLimitsWidget extends UsageLimitsWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return 'Suivi Kilométrique (Leasing)';
    }

    protected function getLimits(): array
    {
        $contracts = VehicleContract::where('type', 'leasing')
            ->whereNotNull('max_mileage')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->with('vehicle')
            ->get();

        return $contracts->map(function ($contract) {
            $vehicle = $contract->vehicle;

            return UsageLimit::make($vehicle->getDisplayName(), (float) $vehicle->odometer, (float) $contract->max_mileage)
                ->color(function ($used, $max) {
                    if ($max == 0) {
                        return 'gray';
                    }
                    $ratio = $used / $max;
                    if ($ratio >= 0.9) {
                        return 'danger';
                    }
                    if ($ratio >= 0.75) {
                        return 'warning';
                    }

                    return 'success';
                });
        })->toArray();
    }
}
