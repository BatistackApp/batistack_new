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
        // On récupère tous les contrats expirés qui pourraient encore avoir un impact
        // Pour être sûr, on vérifie les employés qui ont un contrat échu hier
        $expiredContracts = Contract::whereDate('end_date', '<', today())->get();

        foreach ($expiredContracts as $contract) {
            $employee = $contract->employee;
            
            if ($employee && $employee->user) {
                // S'il n'a pas de contrat actif en cours
                if (! $employee->contracts()->active()->exists()) {
                    if (\Spatie\Permission\Models\Role::where('name', $contract->job_title)->exists()) {
                        $employee->user->removeRole($contract->job_title);
                        $this->info("Rôle retiré pour {$employee->user->email} suite à l'expiration du contrat.");
                    }
                } else {
                    // S'il a un autre contrat actif, on s'assure qu'il a le bon rôle
                    $activeContract = $employee->contracts()->active()->latest()->first();
                    if (\Spatie\Permission\Models\Role::where('name', $activeContract->job_title)->exists()) {
                        $employee->user->assignRole($activeContract->job_title);
                    }
                }
            }
        }
        
        $this->info("Synchronisation des rôles pour les contrats expirés terminée.");
    }
}
