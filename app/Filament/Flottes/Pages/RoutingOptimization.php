<?php

namespace App\Filament\Flottes\Pages;

use App\Enums\Chantiers\ChantierStatus;
use App\Enums\Flottes\AssignmentStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Services\Flottes\RoutingOptimizationService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class RoutingOptimization extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-map';

    protected static string|null|\UnitEnum $navigationGroup = 'Exploitation';

    protected static ?string $title = 'Optimisation des Trajets';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.flottes.pages.routing-optimization';

    public array $suggestions = [];

    public bool $isGenerating = false;

    public function generateSuggestions(RoutingOptimizationService $routingService)
    {
        $this->isGenerating = true;

        // Fetch unassigned vehicles
        $assignedVehicleIds = VehicleAssignment::where('status', AssignmentStatus::ACTIVE)->pluck('vehicle_id');
        $vehicles = Vehicle::whereNotIn('id', $assignedVehicleIds)->get();

        // Fetch active chantiers
        $chantiers = Chantier::whereIn('status', [ChantierStatus::IN_PROGRESS, ChantierStatus::PLANNED])->get();

        if ($vehicles->isEmpty() || $chantiers->isEmpty()) {
            $this->isGenerating = false;
            Notification::make()
                ->title('Aucune donnée')
                ->body('Il n\'y a pas assez de véhicules disponibles ou de chantiers actifs pour optimiser les trajets.')
                ->warning()
                ->send();

            return;
        }

        try {
            $this->suggestions = $routingService->optimizeAssignments($vehicles, $chantiers, 'Siège Social');

            Notification::make()
                ->title('Optimisation réussie')
                ->body(count($this->suggestions).' suggestions ont été générées.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur d\'optimisation')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->isGenerating = false;
    }

    public function confirmAssignments()
    {
        if (empty($this->suggestions)) {
            return;
        }

        $count = 0;
        foreach ($this->suggestions as $suggestion) {
            $chantier = Chantier::find($suggestion['chantier_id']);

            VehicleAssignment::create([
                'vehicle_id' => $suggestion['vehicle_id'],
                'chantier_id' => $suggestion['chantier_id'],
                'started_at' => now(),
                'status' => AssignmentStatus::ACTIVE,
                'employee_id' => $chantier?->manager_id ?? 1,
                'purpose' => 'Optimisation Routing IA (Automatique)',
            ]);
            $count++;
        }

        $this->suggestions = [];

        Notification::make()
            ->title('Affectations créées')
            ->body("$count véhicules ont été affectés aux chantiers avec succès.")
            ->success()
            ->send();
    }
}
