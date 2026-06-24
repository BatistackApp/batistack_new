<?php

namespace App\Console\Commands\Flottes;

use App\Jobs\Flottes\GenerateFleetReportsJob;
use App\Models\Flottes\Vehicle;
use App\Services\Flottes\FleetCostService;
use Illuminate\Console\Command;

class FleetReportCommand extends Command
{
    protected $signature = 'flottes:generate-reports
                            {vehicle? : Référence véhicule spécifique}
                            {--period=month : Period (day/week/month/year)}
                            {--format=json : Format sortie}';

    protected $description = 'Génère rapports flotte : TCO, maintenance, consommation, utilisation';

    public function handle(FleetCostService $costService): int
    {
        $this->info('=== Génération Rapports Flotte ===');

        $vehicleRef = $this->argument('vehicle');
        $period = $this->option('period');

        if ($vehicleRef) {
            // Rapport véhicule spécifique
            $vehicle = Vehicle::where('reference', $vehicleRef)->first();

            if (! $vehicle) {
                $this->error("Véhicule '{$vehicleRef}' introuvable");

                return self::FAILURE;
            }

            $this->generateVehicleReport($vehicle, $costService);
        } else {
            // Rapports pour tous les véhicules
            $this->line('📊 Génération rapports pour tous les véhicules...');
            GenerateFleetReportsJob::dispatch();
            $this->info('✓ Job de génération envoyé en queue');
        }

        return self::SUCCESS;
    }

    protected function generateVehicleReport(Vehicle $vehicle, FleetCostService $costService): void
    {
        $this->line("📄 Rapport véhicule : <fg=cyan>{$vehicle->reference}</fg=cyan>");
        $this->newLine();

        // TCO
        $tco = $costService->calculateTco($vehicle);
        $this->line('💰 <fg=yellow>Coûts Totaux (TCO)</fg=yellow>');
        $this->line('  • TCO global : '.number_format($tco, 2, ',', ' ').'€');
        $this->line('  • Coût/km : '.number_format($costService->getCostPerKilometer($vehicle), 4, ',', ' ').'€');
        $this->line('  • Coût mensuel : '.number_format($costService->getMonthlyTcoCost($vehicle), 2, ',', ' ').'€');

        // Maintenance
        $maintenanceCost = $vehicle->maintenances()->sum('cost_ht');
        $this->newLine();
        $this->line('🔧 <fg=yellow>Maintenance</fg=yellow>');
        $this->line('  • Coût total : '.number_format($maintenanceCost, 2, ',', ' ').'€');
        $this->line('  • Coût/km : '.number_format($costService->getMaintenanceCostPerKm($vehicle), 4, ',', ' ').'€');
        $this->line('  • Prédiction : '.number_format($costService->predictNextMaintenanceCost($vehicle), 2, ',', ' ').'€');

        // Utilisation
        $totalKm = $vehicle->odometer;
        $assignments = $vehicle->assignments()->completed()->count();
        $this->newLine();
        $this->line('📊 <fg=yellow>Utilisation</fg=yellow>');
        $this->line('  • Kilométrage actuel : '.number_format($totalKm, 0, ',', ' ').' km');
        $this->line("  • Affectations complétées : {$assignments}");

        // Contrats
        $contracts = $vehicle->contracts()->count();
        $expiringContracts = $vehicle->contracts()
            ->where('end_date', '<=', now()->addDays(30))
            ->where('end_date', '>', now())
            ->count();

        $this->newLine();
        $this->line('📋 <fg=yellow>Contrats</fg=yellow>');
        $this->line("  • Total contrats : {$contracts}");
        $this->line("  • Expiration -30j : {$expiringContracts}");

        // Amendes
        $fines = $vehicle->fines()->count();
        $unpaidFines = $vehicle->fines()->whereIn('status', ['received', 'disputed'])->sum('amount');

        $this->newLine();
        $this->line('🚨 <fg=yellow>Amendes</fg=yellow>');
        $this->line("  • Total amendes : {$fines}");
        $this->line('  • Montant impayé : '.number_format($unpaidFines, 2, ',', ' ').'€');

        $this->newLine();
        $this->info('✓ Rapport généré');
    }
}
