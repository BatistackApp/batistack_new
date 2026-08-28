<?php

use App\Enums\Interventions\MaintenanceContractFrequency;
use App\Enums\Interventions\MaintenanceContractStatus;
use App\Models\Core\Company;
use App\Models\Interventions\MaintenanceContract;
use App\Services\Interventions\MaintenanceContractDocumentService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Company::factory()->create();
    Storage::fake('public');

    $this->service = Mockery::mock(MaintenanceContractDocumentService::class)->makePartial();
    $this->service->shouldReceive('generate')
        ->once()
        ->andReturn('documents/interventions/contracts/contrat_MC-2026-0001.pdf');

    $this->contract = MaintenanceContract::factory()->create([
        'frequency' => MaintenanceContractFrequency::ANNUAL,
        'status' => MaintenanceContractStatus::ACTIVE,
        'start_date' => now()->toDateString(),
        'flat_rate_price' => 630,
    ]);
});

describe('MaintenanceContractDocumentService - generateContractPdf', function () {
    test('génère le PDF et retourne le chemin relatif du document', function () {
        $path = $this->service->generateContractPdf($this->contract);

        expect($path)->toContain('documents/interventions/contracts/contrat_')
            ->and($path)->toContain($this->contract->reference)
            ->and($path)->toEndWith('.pdf');
    });

    test('charge les relations du contrat avant génération', function () {
        $this->contract->loadMissing('thirdParty');

        $this->service->generateContractPdf($this->contract);

        expect($this->contract->relationLoaded('thirdParty'))->toBeTrue()
            ->and($this->contract->relationLoaded('clientEquipment'))->toBeTrue()
            ->and($this->contract->relationLoaded('chantier'))->toBeTrue();
    });
});
