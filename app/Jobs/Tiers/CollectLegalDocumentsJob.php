<?php

namespace App\Jobs\Tiers;

use App\Contracts\Tiers\LegalDocumentProviderInterface;
use App\Enums\Tiers\ThirdPartyDocumentStatus;
use App\Enums\Tiers\ThirdPartyDocumentType;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Tiers\ThirdParty;
use App\Models\Tiers\ThirdPartyDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CollectLegalDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public ThirdParty $thirdParty) {}

    public function handle(LegalDocumentProviderInterface $provider): void
    {
        if (empty($this->thirdParty->siren)) {
            return;
        }

        if (! in_array($this->thirdParty->type, [ThirdPartyType::SUBCONTRACTOR, ThirdPartyType::CLIENT])) {
            return;
        }

        $this->collectUrssaf($provider);
        $this->collectRne($provider);

        $this->thirdParty->update(['last_legal_sync_at' => now()]);

        Log::info("CollectLegalDocumentsJob: Legal documents collected for ThirdParty {$this->thirdParty->id} (SIREN: {$this->thirdParty->siren}).");
    }

    protected function collectUrssaf(LegalDocumentProviderInterface $provider): void
    {
        try {
            $result = $provider->fetchAttestationUrssaf($this->thirdParty->siren);

            if ($result === null) {
                return;
            }

            $document = ThirdPartyDocument::updateOrCreate(
                [
                    'third_party_id' => $this->thirdParty->id,
                    'type' => ThirdPartyDocumentType::URSSAF,
                ],
                [
                    'expiration_date' => $result['validity_end_date'] ?? null,
                    'status' => ThirdPartyDocumentStatus::VALID,
                ]
            );

            $filename = "attestation_urssaf_{$this->thirdParty->siren}_".now()->format('Ymd_His').'.pdf';
            $document->addMediaFromBase64(base64_encode($result['file_content']))
                ->usingName($filename)
                ->usingFileName($filename)
                ->toMediaCollection('third_party_documents');

            Log::info("CollectLegalDocumentsJob: URSSAF attestation saved for ThirdParty {$this->thirdParty->id}.");
        } catch (\Exception $e) {
            Log::error("CollectLegalDocumentsJob: Failed to collect URSSAF for ThirdParty {$this->thirdParty->id}: ".$e->getMessage());
        }
    }

    protected function collectRne(LegalDocumentProviderInterface $provider): void
    {
        try {
            $result = $provider->fetchAttestationRne($this->thirdParty->siren);

            if ($result === null) {
                return;
            }

            $document = ThirdPartyDocument::updateOrCreate(
                [
                    'third_party_id' => $this->thirdParty->id,
                    'type' => ThirdPartyDocumentType::KBIS,
                ],
                [
                    'expiration_date' => null,
                    'status' => ThirdPartyDocumentStatus::VALID,
                ]
            );

            $filename = "attestation_rne_{$this->thirdParty->siren}_".now()->format('Ymd_His').'.pdf';
            $document->addMediaFromBase64(base64_encode($result['file_content']))
                ->usingName($filename)
                ->usingFileName($filename)
                ->toMediaCollection('third_party_documents');

            Log::info("CollectLegalDocumentsJob: RNE attestation saved for ThirdParty {$this->thirdParty->id}.");
        } catch (\Exception $e) {
            Log::error("CollectLegalDocumentsJob: Failed to collect RNE for ThirdParty {$this->thirdParty->id}: ".$e->getMessage());
        }
    }
}
