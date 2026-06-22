<?php

namespace App\Console\Commands\RH;

use App\Jobs\RH\CheckEquipementMaintenanceJob;
use App\Models\RH\Equipement;
use Illuminate\Console\Command;

class CheckEquipementCommand extends Command
{
    protected $signature = 'rh:check-equipement
                            {--send : Envoyer les alertes immédiatement}';

    protected $description = 'Scan des équipements expirés ou en attente de maintenance';

    public function handle(): int
    {
        $send = $this->option('send');

        $this->info('Scan des équipements...');

        // Équipements expirés
        $expired = Equipement::where('expires_at', '<', now())->get();
        $expiredCount = $expired->count();

        // Équipements nécessitant maintenance
        $needMaintenance = Equipement::where(fn ($q) => $q->whereNull('last_check_at')
            ->orWhere('last_check_at', '<', now()->subDays(365))
        )->get();
        $maintenanceCount = $needMaintenance->count();

        if ($expiredCount === 0 && $maintenanceCount === 0) {
            $this->info('✓ Tous les équipements sont à jour');

            return Command::SUCCESS;
        }

        if ($expiredCount > 0) {
            $this->error("⚠ {$expiredCount} équipement(s) EXPIRÉ(S):");
            $expired->each(fn ($e) => $this->line(" • {$e->label} - {$e->employee->getFullName()}"));
        }

        if ($maintenanceCount > 0) {
            $this->warn("⚠ {$maintenanceCount} équipement(s) en attente de maintenance:");
            $needMaintenance->each(fn ($e) => $this->line(" • {$e->label} - {$e->employee->getFullName()}"));
        }

        if ($send) {
            $this->info('Envoi des alertes...');
            CheckEquipementMaintenanceJob::dispatch();
            $this->info('✓ Job envoyé en file d\'attente');
        } else {
            $this->info('Utilisez --send pour envoyer les alertes');
        }

        return Command::SUCCESS;
    }
}
