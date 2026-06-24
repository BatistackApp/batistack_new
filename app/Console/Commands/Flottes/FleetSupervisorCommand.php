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
                            {--alerts : Scans conformité + contrats + révisions}
                            {--overdue : Détecte retards restitution}
                            {--import-fuel= : CSV carburant (chemin relatif)}
                            {--all : Force exécution complète}';

    protected $description = 'Orchestrateur compliance flotte : contrats, maintenance, retards, imports carburant';

    public function handle(): int
    {
        $this->info('=== Supervision Flotte Batistack ===');
        $this->newLine();

        $alertsOnly = $this->option('alerts');
        $overdueOnly = $this->option('overdue');
        $fuelPath = $this->option('import-fuel');
        $all = $this->option('all');

        // Priorité: import carburant
        if ($fuelPath) {
            return $this->handleFuelImport($fuelPath);
        }

        // Filtres ou exécution complète
        if ($all || (! $alertsOnly && ! $overdueOnly)) {
            $this->scanAlerts();
            $this->newLine();
            $this->scanOverdue();
            $this->newLine();
        } elseif ($alertsOnly) {
            $this->scanAlerts();
        } elseif ($overdueOnly) {
            $this->scanOverdue();
        }

        $this->info('✅ Supervision complétée avec succès.');

        return Command::SUCCESS;
    }

    /**
     * Scans conformité et maintenance.
     */
    protected function scanAlerts(): void
    {
        $this->line('📋 <fg=cyan>Scan Conformité & Contrats</fg=cyan>');
        $this->line('  → Vérification contrats expiration -30j');
        $this->line('  → Polices assurance/leasing/autres');
        CheckExpiringContractsJob::dispatch();
        $this->info('  ✓ Job envoyé en queue');

        $this->newLine();
        $this->line('🔧 <fg=cyan>Scan Maintenance Préventive</fg=cyan>');
        $this->line('  → Véhicules dépassant seuil KM (20 000km)');
        $this->line('  → Alertes révision imminente');
        CheckVehicleMaintenanceMilestonesJob::dispatch();
        $this->info('  ✓ Job envoyé en queue');
    }

    /**
     * Détecte retards restitution.
     */
    protected function scanOverdue(): void
    {
        $this->line('🕒 <fg=cyan>Scan Retards Restitution</fg=cyan>');
        $this->line('  → Affectations dépassant délai +2h');
        $this->line('  → Véhicules bloqués > 24h sans clôture');
        DetectOverdueAssignmentsJob::dispatch();
        $this->info('  ✓ Job envoyé en queue');
    }

    /**
     * Import CSV carburant externe.
     */
    protected function handleFuelImport(string $relativePath): int
    {
        $this->line('⛽ <fg=cyan>Import Carburant</fg=cyan>');
        $this->line("   Chemin : storage/app/{$relativePath}");

        if (! Storage::disk('local')->exists($relativePath)) {
            $this->error("❌ Fichier introuvable : storage/app/{$relativePath}");

            return self::FAILURE;
        }

        try {
            $filePath = Storage::disk('local')->path($relativePath);
            $file = fopen($filePath, 'r');

            if (! $file) {
                throw new Exception("Impossible d'ouvrir le fichier");
            }

            $headers = fgetcsv($file, 1000, ';');
            if (! $headers) {
                throw new Exception('Fichier vide ou en-têtes manquants');
            }

            $transactions = [];
            $rowCount = 0;

            while (($row = fgetcsv($file, 1000, ';')) !== false) {
                $data = array_combine($headers, $row);

                if (! $data || empty($data['license_plate'])) {
                    continue;
                }

                $transactions[] = [
                    'license_plate' => trim($data['license_plate'] ?? ''),
                    'liters' => (float) ($data['liters'] ?? 0),
                    'cost_ht' => (float) ($data['cost_ht'] ?? 0),
                    'odometer' => (float) ($data['odometer'] ?? 0),
                    'date' => $data['date'] ?? now()->toDateTimeString(),
                    'station_name' => $data['station_name'] ?? 'Import CSV',
                ];

                $rowCount++;
            }

            fclose($file);

            if (empty($transactions)) {
                $this->warn('⚠️ Aucune transaction valide trouvée');

                return self::SUCCESS;
            }

            $this->line("📊 Transactions à traiter : <fg=green>{$rowCount}</fg=green>");

            // Dispatch asynchrone
            ProcessExternalFuelCardImportJob::dispatch($transactions);

            $this->info('✅ Importation initiée - Analyse fraude en cours');
            $this->line('   Moteur détection: Consommation aberrante / Odomètre incohérent / Prix suspect');

            return self::SUCCESS;

        } catch (Exception $e) {
            $this->error("❌ Erreur import : {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
