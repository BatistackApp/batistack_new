<?php

namespace App\Filament\Immobilisation\Widgets;

use App\Models\Immobilisation\FixedAsset;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\DetailListWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\Detail;
use App\Filament\Immobilisation\Resources\FixedAssetResource;

class AssetAlertsDetailWidget extends DetailListWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Alertes Rentabilité & VGP (Actions requises)';
    }

    protected function getDetails(): array
    {
        $assets = FixedAsset::with(['maintenances', 'depreciations', 'impairments'])->get();
        $details = [];

        $currentYearStart = now()->startOfYear();

        foreach ($assets as $asset) {
            $issues = [];

            // 1. VGP Status Check
            if ($asset->vgp_frequency_months > 0) {
                if ($asset->vgp_status === 'danger') {
                    $issues[] = 'VGP expirée depuis le ' . ($asset->next_vgp_date ? $asset->next_vgp_date->format('d/m/Y') : 'inconnu');
                } elseif ($asset->vgp_status === 'warning') {
                    $issues[] = 'VGP à renouveler avant le ' . ($asset->next_vgp_date ? $asset->next_vgp_date->format('d/m/Y') : 'inconnu');
                }
            }

            // 2. Rentability Check (Cost > VNC)
            $currentVnc = max(0, $asset->purchase_price 
                - $asset->depreciations->where('is_passed', true)->sum('amount') 
                - $asset->impairments->sum('amount')
            );
            
            $yearlyMaintenanceCost = $asset->maintenances
                ->where('maintenance_date', '>=', $currentYearStart)
                ->sum('cost');

            if ($currentVnc > 0 && $yearlyMaintenanceCost > $currentVnc) {
                $issues[] = 'Coût maintenance annuel (' . number_format($yearlyMaintenanceCost, 0) . '€) > VNC (' . number_format($currentVnc, 0) . '€)';
            }

            if (!empty($issues)) {
                $icon = in_array($asset->vgp_status, ['danger', 'warning']) ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-currency-euro';
                $color = 'danger';
                
                $details[] = Detail::make($asset->name . ' (' . $asset->serial_number . ')', implode(' | ', $issues))
                    ->icon($icon)
                    ->color($color)
                    ->url(FixedAssetResource::getUrl('edit', ['record' => $asset]));
            }
        }

        return $details;
    }
}
