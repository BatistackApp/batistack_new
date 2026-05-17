<?php

namespace App\Console\Commands\Flottes;

use App\Jobs\Flottes\CheckExpiringContractsJob;
use App\Jobs\Flottes\CheckVehicleMaintenanceMilestonesJob;
use App\Jobs\Flottes\DetectOverdueAssignmentsJob;
use App\Jobs\Flottes\ProcessExternalFuelCardImportJob;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FleetSupervisorCommand extends Command
{
    protected $signature = 'flottes:fleet-supervisor
                            {--alerts : Exécute uniquement les scans d\'échéances de contrats et de révisions}
                            {--overdue : Détecte uniquement les retards de restitution de véhicules}
                            {--import-fuel= : Chemin d\'accès relatif au disque local vers le fichier CSV de transactions carburant}';

    protected $description = 'Orchestrateur de conformité réglementaire, de suivi de maintenance et d\'import d\'énergies pour le parc de véhicules.';

    public function handle(): int
    {
        $this->info('--- Tour de contrôle de la Flotte Batistack ---');

        $alertsOnly = $this->option('alerts');
        $overdueOnly = $this->option('overdue');
        $fuelPath = $this->option('import-fuel');

        // Cas 1 : Importation de carte carburant
        if ($fuelPath) {
            return $this->handleFuelImport($fuelPath);
        }

        // Cas 2 : Filtres ciblés ou exécution complète
        if ($alertsOnly) {
            $this->scanAlerts();

            return self::SUCCESS;
        }

        if ($overdueOnly) {
            $this->scanOverdue();

            return self::SUCCESS;
        }

        // Exécution globale par défaut
        $this->scanAlerts();
        $this->newLine();
        $this->scanOverdue();

        $this->info('Supervision de la flotte terminée avec succès.');

        return self::SUCCESS;
    }

    /**
     * Déclenche les scans de vigilance et de maintenance.
     */
    protected function scanAlerts(): void
    {
        $this->line('1. Lancement du scan de conformité et des échéances de contrats...');
        CheckExpiringContractsJob::dispatch();
        $this->info('   -> Job de vérification des contrats (Assurances/Leasing) envoyé en file d\'attente.');

        $this->line('2. Vérification des jalons de révision kilométrique (milestones)...');
        CheckVehicleMaintenanceMilestonesJob::dispatch();
        $this->info('   -> Job d\'analyse préventive des odomètres envoyé en file d\'attente.');
    }

    /**
     * Détecte les anomalies de restitution terrain.
     */
    protected function scanOverdue(): void
    {
        $this->line('3. Analyse des affectations actives et détection des dépassements d\'horaires...');
        DetectOverdueAssignmentsJob::dispatch();
        $this->info('   -> Job de contrôle de restitution de véhicules envoyé en file d\'attente.');
    }

    /**
     * Parse un fichier CSV externe et déclenche l'importation asynchrone sécurisée.
     */
    protected function handleFuelImport(string $relativePath): int
    {
        $this->line("Préparation de l'importation du fichier carburant : {$relativePath}...");

        if (! Storage::disk('local')->exists($relativePath)) {
            $this->error("Fichier introuvable sur le disque local : storage/app/{$relativePath}");

            return self::FAILURE;
        }

        try {
            $filePath = Storage::disk('local')->path($relativePath);
            $file = fopen($filePath, 'r');

            $headers = fgetcsv($file, 1000, ';'); // Séparateur point-virgule standard d'export comptable
            $transactions = [];

            while (($row = fgetcsv($file, 1000, ';')) !== false) {
                // Mapping dynamique simple (license_plate;liters;cost_ht;odometer;date)
                $data = array_combine($headers, $row);

                $transactions[] = [
                    'license_plate' => $data['license_plate'] ?? null,
                    'liters' => (float) ($data['liters'] ?? 0),
                    'cost_ht' => (float) ($data['cost_ht'] ?? 0),
                    'odometer' => (float) ($data['odometer'] ?? 0),
                    'date' => $data['date'] ?? now()->toDateTimeString(),
                ];
            }

            fclose($file);

            if (empty($transactions)) {
                $this->warn('Le fichier importé ne contient aucune transaction valide.');

                return self::SUCCESS;
            }

            $this->line('Envoi du lot de '.count($transactions).' transactions au validateur asynchrone...');

            // Dispatch du Job asynchrone que vous aviez sélectionné dans le Canvas
            ProcessExternalFuelCardImportJob::dispatch($transactions);

            $this->info('Importation initiée ! Le moteur de détection des fraudes analyse actuellement le lot en tâche de fond.');

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error('Erreur critique lors de la lecture du fichier : '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
