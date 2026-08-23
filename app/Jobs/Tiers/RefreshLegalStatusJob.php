<?php

namespace App\Jobs\Tiers;

use App\Enums\Tiers\LegalStatus;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Notifications\Tiers\LegalStatusAlertNotification;
use App\Services\Tiers\PappersService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshLegalStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ThirdParty $thirdParty
    ) {}

    public function handle(PappersService $pappersService): void
    {
        $previousStatus = $this->thirdParty->legal_status;

        $synced = $pappersService->syncFinancialData($this->thirdParty);

        if (! $synced) {
            Log::warning("Échec de la synchronisation financière et du statut juridique pour le tiers #{$this->thirdParty->id} ({$this->thirdParty->name})");
            return;
        }

        $this->thirdParty->refresh();
        $newStatus = $this->thirdParty->legal_status;

        Log::info("Statut juridique rafraîchi pour le tiers #{$this->thirdParty->id} ({$this->thirdParty->name}) : " . ($newStatus?->value ?? 'null'));

        // Vérifier si le statut a basculé vers une procédure collective / situation critique
        $criticalStatuses = [
            LegalStatus::REDRESSEMENT_JUDICIAIRE,
            LegalStatus::LIQUIDATION_JUDICIAIRE,
            LegalStatus::CESSATION,
        ];

        if ($newStatus && in_array($newStatus, $criticalStatuses) && $newStatus !== $previousStatus) {
            Log::alert("Alerte procédure collective détectée pour le tiers #{$this->thirdParty->id} ({$this->thirdParty->name}) : statut {$newStatus->value}");

            $admins = User::where('is_admin', true)->get();
            foreach ($admins as $admin) {
                $admin->notify(new LegalStatusAlertNotification($this->thirdParty, $previousStatus, $newStatus));
            }
        }
    }
}
