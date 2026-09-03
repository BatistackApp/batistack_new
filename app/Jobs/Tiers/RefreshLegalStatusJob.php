<?php

namespace App\Jobs\Tiers;

use App\Enums\Tiers\LegalStatus;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Notifications\Tiers\LegalStatusChangedNotification;
use App\Services\Tiers\PappersService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshLegalStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function handle(PappersService $pappersService): void
    {
        $threshold = Carbon::now()->subDays(7);

        $thirdParties = ThirdParty::where('is_active', true)
            ->whereNotNull('siren')
            ->cursor();

        $total = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($thirdParties as $thirdParty) {
            $total++;

            if ($thirdParty->last_financial_sync_at && $thirdParty->last_financial_sync_at->isAfter($threshold)) {
                $skipped++;
                continue;
            }

            $oldStatus = $thirdParty->legal_status;

            try {
                $success = $pappersService->syncFinancialData($thirdParty);

                if (! $success) {
                    Log::warning("RefreshLegalStatusJob: échec de la synchro pour le tiers {$thirdParty->id} ({$thirdParty->name})");
                    $errors++;
                    continue;
                }

                $thirdParty->refresh();

                if ($oldStatus !== $thirdParty->legal_status) {
                    Log::info("RefreshLegalStatusJob: statut juridique mis à jour pour le tiers {$thirdParty->id} ({$thirdParty->name})", [
                        'old_status' => $oldStatus?->value,
                        'new_status' => $thirdParty->legal_status?->value,
                    ]);
                    $updated++;

                    if ($thirdParty->legal_status === LegalStatus::REDRESSEMENT_JUDICIAIRE
                        || $thirdParty->legal_status === LegalStatus::LIQUIDATION_JUDICIAIRE
                    ) {
                        $managers = User::where('is_admin', true)->get();

                        foreach ($managers as $manager) {
                            $manager->notify(new LegalStatusChangedNotification($thirdParty, $oldStatus, $thirdParty->legal_status));
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("RefreshLegalStatusJob: erreur pour le tiers {$thirdParty->id} ({$thirdParty->name}): ".$e->getMessage());
                $errors++;
            }
        }

        Log::info('RefreshLegalStatusJob: terminé', [
            'total' => $total,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }
}
