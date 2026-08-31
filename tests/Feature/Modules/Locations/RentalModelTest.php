<?php

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Chantiers\Chantier;
use App\Models\Locations\RentalContract;
use App\Models\Locations\RentalContractLine;
use App\Models\Tiers\ThirdParty;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create a rental contract with supplier and chantier', function () {
    $supplier = ThirdParty::factory()->create(['type' => ThirdPartyType::SUPPLIER]);
    $chantier = Chantier::factory()->create();

    $contract = RentalContract::factory()->create([
        'supplier_id' => $supplier->id,
        'chantier_id' => $chantier->id,
    ]);

    expect($contract->supplier->id)->toBe($supplier->id)
        ->and($contract->chantier->id)->toBe($chantier->id);
});

it('can have lines associated to a contract', function () {
    $contract = RentalContract::factory()->create();

    RentalContractLine::factory()->count(3)->create([
        'rental_contract_id' => $contract->id,
    ]);

    expect($contract->lines)->toHaveCount(3);
});
