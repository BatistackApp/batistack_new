<?php

namespace App\Jobs\RH;

use App\Models\RH\MedicalVisit;
use App\Models\User;
use App\Notifications\RH\MedicalVisitReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Log;

class ScanExpiringMedicalVisitsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Notifier les RH pour les visites à J-30
        $visits = MedicalVisit::where('next_due_date', '>=', now()->addDays(30))
            ->where('next_due_date', '<', now()->addDays(31))
            ->with('employee')
            ->get();

        if ($visits->isEmpty()) {
            Log::info('ScanExpiringMedicalVisitsJob: No medical visits due in 30 days');

            return;
        }

        $managers = User::where('is_admin', true)->get();

        foreach ($visits as $visit) {
            Notification::send($managers, new MedicalVisitReminderNotification($visit));
            Log::info('Medical visit reminder sent', ['visit_id' => $visit->id, 'employee_id' => $visit->employee_id]);
        }
    }
}
