<?php

namespace App\Jobs\Flottes;

use App\Models\Flottes\TrafficFine;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Log;

class ScanExpiringFinesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $managers = User::where('is_admin', true)->get();

        // Amendes en attente de paiement depuis + de 30 jours
        $pendingFines = TrafficFine::query()
            ->whereIn('status', ['received', 'disputed'])
            ->where('infraction_at', '<', now()->subDays(30))
            ->with(['vehicle', 'employee'])
            ->get();

        foreach ($pendingFines as $fine) {
            $daysOverdue = $fine->infraction_at->diffInDays(now()) - 45; // Délai de paiement = 45j

            if ($daysOverdue > 0) {
                Log::warning("Amende en retard : {$fine->reference} - {$daysOverdue} jours après délai");
                Notification::send($managers, new TrafficFineReminderNotification($fine, $daysOverdue));
            }
        }

        Log::info("Scan amendes : {$pendingFines->count()} amendes en attente");
    }
}
