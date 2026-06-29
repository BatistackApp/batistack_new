<?php

namespace App\Jobs\Tiers;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Notifications\Tiers\VigilanceExpirationNotification;
use App\Services\Tiers\VigilanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyGloabVigilanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(VigilanceService $vigilanceService): void
    {
        $subcontractors = ThirdParty::where('type', ThirdPartyType::SUBCONTRACTOR->value)
            ->where('is_active', true)
            ->get();

        foreach ($subcontractors as $subcontractor) {
            $compliance = $vigilanceService->scanCompliance($subcontractor);
            $subcontractor->update([
                'compliant_status' => $compliance,
            ]);

            if (! $compliance['compliant']) {
                $managers = User::where('is_admin', true)->get();

                foreach ($managers as $manager) {
                    $manager->notify(new VigilanceExpirationNotification($subcontractor, array_merge($compliance['issues'])));
                }

                // Notifier le sous-traitant (ou son contact principal)
                $targetEmail = $subcontractor->email ?? $subcontractor->getPrimaryContact()?->email;
                if ($targetEmail) {
                    \Illuminate\Support\Facades\Notification::route('mail', $targetEmail)
                        ->notify(new \App\Notifications\Tiers\SubcontractorVigilanceReminderNotification($subcontractor, $compliance['issues']));
                }
            }
        }
    }
}
