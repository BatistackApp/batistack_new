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
        // Affectations dépassant l'heure de retour prévue de +2h
        $overdueAssignments = VehicleAssignment::query()
            ->where('status', AssignmentStatus::ACTIVE)
            ->whereNotNull('ended_at')
            ->where('ended_at', '<', now()->subHours(2))
            ->with(['vehicle', 'employee', 'chantier'])
            ->get();

        if ($overdueAssignments->isEmpty()) {
            return;
        }

        $managers = User::where('is_admin', true)->get();

        foreach ($overdueAssignments as $assignment) {
            $hoursOverdue = $assignment->ended_at->diffInHours(now());

            Notification::send($managers, new OverdueAssignmentNotification($assignment, $hoursOverdue));

            Log::warning("Restitution tardive : {$assignment->vehicle->reference} - {$hoursOverdue}h de retard");
        }

        // Affectations sans state de clôture depuis 24h
        $stuckAssignments = VehicleAssignment::query()
            ->where('status', AssignmentStatus::ACTIVE)
            ->whereNull('ended_at')
            ->where('started_at', '<', now()->subDays(1))
            ->with(['vehicle', 'employee'])
            ->get();

        foreach ($stuckAssignments as $assignment) {
            $daysElapsed = $assignment->started_at->diffInDays(now());

            Log::warning("Affectation bloquée : {$assignment->vehicle->reference} - {$daysElapsed} jours sans clôture");

            Notification::send($managers, new OverdueAssignmentNotification($assignment, $daysElapsed * 24));
        }
    }
}
