<?php

namespace App\Jobs\RH;

use App\Models\RH\Contract;
use App\Models\User;
use App\Notifications\RH\TrialPeriodEndingNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TrialPeriodAlerterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // On cherche les contrats dont la période d'essai (simulée ici par une date) finit dans 15 jours
        $contracts = Contract::whereNotNull('trial_end_date')
            ->whereDate('trial_end_date', now()->addDays(15)->toDateString())
            ->with('employee')
            ->get();

        $rhUsers = User::admin(); // À filtrer par rôle RH

        foreach ($contracts as $contract) {
            foreach ($rhUsers as $user) {
                $user->notify(new TrialPeriodEndingNotification($contract));
            }
        }
    }
}
