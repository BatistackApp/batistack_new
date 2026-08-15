<?php

use App\Enums\Articles\HazardCategory;
use App\Enums\Securite\RiskType;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierPhase;
use App\Models\Chantiers\ChantierTask;
use App\Models\Chantiers\ResourceAllocation;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use App\Services\Chantiers\PpspsService;

it('compile le PPSPS en croisant produits, matériel et risques', function () {
    $chantier = Chantier::factory()->create();
    $warehouse = Warehouse::factory()->create(['chantier_id' => $chantier->id]);

    $item = Item::factory()->create([
        'hazard_category' => HazardCategory::CORROSIVE,
        'ghs_pictograms' => ['ghs05'],
        'h_phrases' => ['H314 Provoque des brûlures de la peau'],
    ]);
    Stock::factory()->create([
        'warehouse_id' => $warehouse->id,
        'item_id' => $item->id,
        'quantity' => 5,
    ]);

    $data = app(PpspsService::class)->build($chantier);

    expect($data['products'])->toHaveCount(1);
    expect($data['materials'])->toHaveCount(1);
    expect($data['materials'][0]['quantity'])->toBe(5);
    expect($data['risks'])->toContain(RiskType::CORROSION);
    expect($data['epi'])->not->toBeEmpty();
    expect($data['collective'])->not->toBeEmpty();
});

it('ne signale aucun risque si aucun produit dangereux', function () {
    $chantier = Chantier::factory()->create();

    $data = app(PpspsService::class)->build($chantier);

    expect($data['risks'])->toBeEmpty();
});

it('compile les produits alloués aux tâches des phases', function () {
    $chantier = Chantier::factory()->create();
    $item = Item::factory()->create([
        'hazard_category' => HazardCategory::FLAMMABLE,
        'ghs_pictograms' => ['ghs02'],
    ]);

    $phase = ChantierPhase::factory()->create(['chantier_id' => $chantier->id]);
    $task = ChantierTask::factory()->create(['chantier_phase_id' => $phase->id]);
    ResourceAllocation::create([
        'chantier_task_id' => $task->id,
        'allocatable_type' => Item::class,
        'allocatable_id' => $item->id,
        'date' => now(),
    ]);

    $data = app(PpspsService::class)->build($chantier);

    expect($data['products'])->toHaveCount(1);
    expect($data['phases'])->not->toBeEmpty();
    expect(collect($data['phases'])->contains(fn ($p) => count($p['products']) === 1))->toBeTrue();
    expect($data['phases'][0]['tasks'])->not->toBeEmpty();
    expect($data['risks'])->toContain(RiskType::INCENDIE);
});

it('compile les sous-traitants et les membres avec visite médicale et qualifications', function () {
    $chantier = Chantier::factory()->create();

    $subcontractor = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    $chantier->subcontractors()->attach($subcontractor->id);

    $employee = Employee::factory()->create();
    $chantier->members()->attach($employee->id);
    $employee->medicalVisits()->create([
        'type' => 'vip',
        'visit_date' => now(),
        'next_due_date' => now()->addYear(),
        'aptitude' => 'fit',
    ]);
    $employee->qualifications()->create([
        'type' => 'electrical',
        'label' => 'B1',
        'reference_number' => 'CERT-0001',
        'obtained_at' => now()->subYear(),
        'expires_at' => now()->addYear(),
    ]);

    $data = app(PpspsService::class)->build($chantier);

    expect($data['subcontractors'])->toHaveCount(1);
    expect($data['members'])->toHaveCount(1);
    expect($data['members'][0]['medical'])->not->toBeNull();
    expect($data['members'][0]['qualifications'])->toHaveCount(1);
});
