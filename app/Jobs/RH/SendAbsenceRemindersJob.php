<?php

namespace App\Jobs\RH;

use App\Models\RH\Abscence;
use App\Models\User;
use App\Notifications\RH\AbsencePendingNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class SendAbsenceRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Absences non encore déclarées à la CIBTP (assimilé 'pending') depuis plus de 3 jours
        $pendingAbsences = Abscence::whereNull('cibtp_declared_at')
            ->where('created_at', '<', now()->subDays(3))
            ->with('employee')
            ->get();

        if ($pendingAbsences->isEmpty()) {
            Log::info('SendAbsenceRemindersJob: No pending absences to remind');
            return;
        }

        $managers = User::where('is_admin', true)->get();

        foreach ($pendingAbsences as $absence) {
            // Notifier les managers
            foreach ($managers as $manager) {
                $manager->notify(new AbsencePendingNotification($absence));
            }

            // Notifier l'employé que son absence est en attente depuis longtemps
            if ($absence->employee) {
                $absence->employee->notify(new AbsencePendingNotification($absence));
            }

            Log::info('Absence pending reminder sent', [
                'absence_id' => $absence->id,
                'employee_id' => $absence->employee_id,
                'days_pending' => $absence->created_at->diffInDays(now()),
            ]);
        }
    }
}
