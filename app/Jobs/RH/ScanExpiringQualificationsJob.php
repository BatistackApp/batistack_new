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

class ScanExpiringQualificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Récupère les habilitations expirant dans exactement 30 jours
        $expiringIn30Days = Qualification::whereDate('expires_at', now()->addDays(30)->toDateString())
            ->with('employee')
            ->get();

        $managers = User::admin()->get(); // À filtrer par rôle RH

        foreach ($expiringIn30Days as $qualification) {
            Notification::send($managers, new QualificationExpiringNotification($qualification, false));

            // On peut aussi notifier l'employé directement s'il a un accès
            if ($qualification->employee->email) {
                $qualification->employee->notify(new QualificationExpiringNotification($qualification, false));
            }
        }
    }
}
