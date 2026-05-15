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

class ScanExpiringMedicalVisitsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // Notifier les RH pour les visites à J-30
        $visits = MedicalVisit::whereDate('next_due_date', now()->addDays(30)->toDateString())->get();
        foreach ($visits as $visit) {
            User::admin()->get()->each->notify(new MedicalVisitReminderNotification($visit));
        }
    }
}
