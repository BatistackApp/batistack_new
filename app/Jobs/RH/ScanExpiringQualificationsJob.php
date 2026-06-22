<?php

namespace App\Jobs\RH;

use App\Models\RH\Qualification;
use App\Models\User;
use App\Notifications\RH\QualificationExpiringNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Log;

class ScanExpiringQualificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Récupère les habilitations expirant dans exactement 30 jours
        $expiringIn30Days = Qualification::where('expiration_date', '>=', now()->addDays(30))
            ->where('expiration_date', '<', now()->addDays(31))
            ->with('employee')
            ->get();

        if ($expiringIn30Days->isEmpty()) {
            Log::info('ScanExpiringQualificationsJob: No qualifications expiring in 30 days');
            return;
        }

        $managers = User::where('is_admin', true)->get();

        foreach ($expiringIn30Days as $qualification) {
            // Notifier les managers RH
            Notification::send($managers, new QualificationExpiringNotification($qualification, false));

            // Notifier l'employé
            if ($qualification->employee && $qualification->employee->email) {
                $qualification->employee->notify(new QualificationExpiringNotification($qualification, false));
            }

            Log::info('Qualification expiration notification sent', ['qualification_id' => $qualification->id, 'employee_id' => $qualification->employee_id]);
        }
    }
}
