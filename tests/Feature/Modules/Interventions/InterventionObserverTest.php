<?php

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Models\Interventions\Intervention;

beforeEach(function () {
    \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys=OFF;');
    $this->company = Company::factory()->create();
    $this->client = ThirdParty::factory()->create();
});

describe('InterventionObserver', function () {
    test('creating sets default company and reference', function () {
        $intervention = Intervention::factory()->create(['company_id' => null, 'reference' => null, 'type' => InterventionType::REGIE, 'status' => InterventionStatus::BROUILLON, 'third_party_id' => $this->client->id]);
        expect($intervention->company_id)->not->toBeNull();
    });

    test('creating increments reference sequence', function () {
        $intervention1 = Intervention::factory()->create(['company_id' => $this->company->id, 'reference' => null, 'type' => InterventionType::REGIE, 'status' => InterventionStatus::BROUILLON, 'third_party_id' => $this->client->id]);
        $intervention2 = Intervention::factory()->create(['company_id' => $this->company->id, 'reference' => null, 'type' => InterventionType::REGIE, 'status' => InterventionStatus::BROUILLON, 'third_party_id' => $this->client->id]);
        
        $seq1 = (int) substr($intervention1->reference, -4);
        $seq2 = (int) substr($intervention2->reference, -4);
        expect($seq2)->toBe($seq1 + 1);
    });

    test('deleted, restored and forceDeleted are handled without errors', function () {
        $intervention = Intervention::factory()->create(['company_id' => $this->company->id, 'third_party_id' => $this->client->id, 'type' => InterventionType::REGIE, 'status' => InterventionStatus::BROUILLON]);
        $intervention->delete();
        $intervention->restore();
        $intervention->forceDelete();
        expect(Intervention::find($intervention->id))->toBeNull();
    });
});
