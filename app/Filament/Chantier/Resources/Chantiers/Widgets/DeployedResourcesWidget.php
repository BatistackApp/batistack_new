<?php

namespace App\Filament\Chantier\Resources\Chantiers\Widgets;

use App\Models\Chantiers\Chantier;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class DeployedResourcesWidget extends Widget
{
    protected string $view = 'filament.chantier.widgets.deployed-resources-widget';

    public ?Model $record = null;

    protected int | string | array $columnSpan = 'full';

    public function getResources(): array
    {
        /** @var Chantier $chantier */
        $chantier = $this->record;
        
        if (!$chantier) {
            return [];
        }

        $resources = [];

        // 1. Assets from Immobilisation
        foreach ($chantier->fixedAssets as $asset) {
            $resources[] = [
                'name' => $asset->name,
                'type' => 'Matériel Propre',
                'supplier' => '-',
                'status' => $asset->status?->getLabel() ?? 'Actif',
                'start_date' => $asset->purchase_date?->format('d/m/Y') ?? '-',
                'end_date' => '-',
                'cost' => 'Amorti / Fixe',
            ];
        }

        // 2. Assets from Rental Contracts
        $chantier->loadMissing(['rentalContracts.lines', 'rentalContracts.supplier']);
        foreach ($chantier->rentalContracts as $contract) {
            foreach ($contract->lines as $line) {
                $resources[] = [
                    'name' => $line->name,
                    'type' => 'Location',
                    'supplier' => $contract->supplier?->name ?? '-',
                    'status' => $contract->status?->getLabel() ?? 'Actif',
                    'start_date' => $contract->start_date?->format('d/m/Y') ?? '-',
                    'end_date' => $contract->end_date?->format('d/m/Y') ?? ($contract->end_date_preview?->format('d/m/Y') ?? '-'),
                    'cost' => number_format($line->unit_price_ht, 2) . ' € / ' . ($contract->billing_period?->getLabel() ?? 'jour'),
                ];
            }
        }

        // 3. Vehicles
        $vehicleAssignments = \App\Models\Flottes\VehicleAssignment::with(['vehicle', 'employee'])
            ->where('chantier_id', $chantier->id)
            ->get();
            
        foreach ($vehicleAssignments as $assignment) {
            $resources[] = [
                'name' => $assignment->vehicle->brand . ' ' . $assignment->vehicle->model . ' (' . $assignment->vehicle->license_plate . ')',
                'type' => 'Véhicule',
                'supplier' => $assignment->employee ? $assignment->employee->full_name : 'Sans conducteur',
                'status' => $assignment->status?->getLabel() ?? 'Actif',
                'start_date' => $assignment->started_at?->format('d/m/Y') ?? '-',
                'end_date' => $assignment->ended_at?->format('d/m/Y') ?? '-',
                'cost' => 'Selon conso (Carburant/Km)',
            ];
        }

        return $resources;
    }
}
