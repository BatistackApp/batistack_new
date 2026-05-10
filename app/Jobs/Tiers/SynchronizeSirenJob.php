<?php

namespace App\Jobs\Tiers;

use App\Models\Tiers\ThirdParty;
use App\Services\Core\SirenService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SynchronizeSirenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ThirdParty $thirdParty) {}

    public function handle(SirenService $sirenService): void
    {
        try {
            if (! $this->thirdParty->siret) {
                Log::warning("SynchronizeSirenJob: ThirdParty {$this->thirdParty->id} has no SIRET to synchronize.");

                return;
            }

            $sirenInfo = $sirenService->getInformation($this->thirdParty->siret);

            if ($sirenInfo) {
                $this->thirdParty->update([
                    'legal_name' => $sirenInfo['uniteLegale']['denominationUniteLegale'] ?? $this->thirdParty->legal_name,
                    // Ajoutez d'autres champs à synchroniser ici si nécessaire
                    // Par exemple: 'address' => $sirenInfo['etablissement']['adresseEtablissement']['libelleVoie'] ?? null,
                ]);
                Log::info("SynchronizeSirenJob: ThirdParty {$this->thirdParty->id} (SIRET: {$this->thirdParty->siret}) synchronized successfully.");
            } else {
                Log::warning("SynchronizeSirenJob: No SIREN information found for ThirdParty {$this->thirdParty->id} with SIRET: {$this->thirdParty->siret}.");
            }
        } catch (\Exception $e) {
            Log::error("SynchronizeSirenJob: Failed to synchronize ThirdParty {$this->thirdParty->id} (SIRET: {$this->thirdParty->siret}). Error: ".$e->getMessage());
        }
    }
}
