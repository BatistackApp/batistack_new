<?php

namespace App\Console\Commands\RH;

use Illuminate\Console\Command;
use App\Models\RH\Contract;
use Log;

class SyncExpiredContractsRolesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rh:sync-expired-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revoke roles for employees whose contracts have expired';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // On récupère les contrats expirés ou qui commencent aujourd'hui
        $contractsToProcess = Contract::whereDate('end_date', '<', today())
            ->orWhereDate('start_date', '<=', today())
            ->get();

        foreach ($contractsToProcess as $contract) {
            $employee = $contract->employee;
            
            if ($employee && $employee->user) {
                // Obtenir tous les postes des contrats actifs de cet employé
                $activeJobTitles = $employee->contracts()->active()->pluck('job_title')->filter()->unique()->toArray();
                
                // Si le contrat n'est pas actif et son rôle n'est pas dans un contrat actif, on le retire
                if (! $contract->isActive() && $contract->job_title && !in_array($contract->job_title, $activeJobTitles)) {
                    if (\Spatie\Permission\Models\Role::where('name', $contract->job_title)->exists()) {
                        $employee->user->removeRole($contract->job_title);
                        $this->info("Rôle retiré pour {$employee->user->email} suite à l'expiration du contrat.");
                    }
                }
                
                // Assigner les rôles pour tous les contrats actifs
                foreach ($activeJobTitles as $jobTitle) {
                    if (\Spatie\Permission\Models\Role::where('name', $jobTitle)->exists()) {
                        $employee->user->assignRole($jobTitle);
                        $this->info("Rôle {$jobTitle} attribué pour {$employee->user->email} suite à l'activation d'un contrat.");
                    }
                }
            }
        }
        
        $this->info("Synchronisation des rôles pour les contrats expirés et actifs terminée.");
    }
}
