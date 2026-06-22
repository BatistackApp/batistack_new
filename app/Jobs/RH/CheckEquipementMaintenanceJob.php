<?php

namespace App\Jobs\RH;

use App\Models\RH\Equipement;
use App\Models\User;
use App\Notifications\RH\EquipementMaintenanceNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CheckEquipementMaintenanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Équipements expirés
        $expiredEquipements = Equipement::where('expires_at', '<', now())->get();

        // Équipements nécessitant une vérification
        $equipementsNeedingCheck = Equipement::where(fn ($q) => $q->whereNull('last_check_at')
            ->orWhere('last_check_at', '<', now()->subDays(365))
        )->get();

        $managers = User::where('is_admin', true)->get();

        // Notifier pour équipements expirés
        foreach ($expiredEquipements as $equipement) {
            Notification::send($managers, new EquipementMaintenanceNotification($equipement, 'expired'));
            Log::warning('Expired equipement detected', ['equipement_id' => $equipement->id, 'employee_id' => $equipement->employee_id]);
        }

        // Notifier pour vérifications manquantes
        foreach ($equipementsNeedingCheck as $equipement) {
            Notification::send($managers, new EquipementMaintenanceNotification($equipement, 'maintenance'));
            Log::warning('Equipement maintenance overdue', ['equipement_id' => $equipement->id, 'employee_id' => $equipement->employee_id]);
        }

        Log::info('CheckEquipementMaintenanceJob completed', [
            'expired_count' => $expiredEquipements->count(),
            'maintenance_count' => $equipementsNeedingCheck->count(),
        ]);
    }
}
