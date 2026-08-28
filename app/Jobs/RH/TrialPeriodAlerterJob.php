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
use Log;

class TrialPeriodAlerterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // On cherche les contrats dont la période d'essai finit dans exactement 15 jours
        $contracts = Contract::whereNotNull('trial_end_date')
            ->where('trial_end_date', '>=', now()->addDays(15))
            ->where('trial_end_date', '<', now()->addDays(16))
            ->with('employee')
            ->get();

        if ($contracts->isEmpty()) {
            Log::info('TrialPeriodAlerterJob: No trial periods ending in 15 days');

            return;
        }

        $rhUsers = User::where('is_admin', true)->get();

        foreach ($contracts as $contract) {
            foreach ($rhUsers as $user) {
                $user->notify(new TrialPeriodEndingNotification($contract));
            }
            Log::info('Trial period ending notification sent', ['contract_id' => $contract->id, 'employee_id' => $contract->employee_id]);
        }
    }
}
