<?php

namespace App\Jobs\Flottes;

use App\Enums\Flottes\AssignmentStatus;
use App\Models\Flottes\VehicleAssignment;
use App\Models\User;
use App\Notifications\Flottes\OverdueAssignmentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Log;

class DetectOverdueAssignmentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $overdueAssignments = VehicleAssignment::query()
            ->where('status', AssignmentStatus::ACTIVE)
            ->whereNotNull('ended_at')
            ->where('ended_at', '<', now()->subHours(2)) // Tolérance de 2h après l'heure de retour prévue
            ->with(['vehicle', 'employee'])
            ->get();

        if ($overdueAssignments->isEmpty()) {
            return;
        }

        $managers = User::admin(); // À filtrer par rôle 'fleet_manager'

        foreach ($overdueAssignments as $assignment) {
            Notification::send($managers, new OverdueAssignmentNotification($assignment));

            Log::warning("Alerte Restitution : Le véhicule {$assignment->vehicle->license_plate} confié à {$assignment->employee->full_name} dépasse l'heure de restitution prévue.");
        }
    }
}
