<?php

use App\Enums\Chantiers\DoeDocumentCategory;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Banque\BankTransaction;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierLog;
use App\Models\Chantiers\ChantierPhase;
use App\Models\Chantiers\ChantierReserve;
use App\Models\Chantiers\DoeDocument;
use App\Models\Chantiers\WeatherAlert;
use App\Models\RH\CibtpDeclaration;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Models\Tiers\ThirdParty;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has client relation', function () {
    $client = ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]);
    $chantier = Chantier::factory()->create(['client_id' => $client->id]);

    expect($chantier->client)->toBeInstanceOf(ThirdParty::class)
        ->and($chantier->client->id)->toBe($client->id);
});

it('has manager relation', function () {
    $manager = Employee::factory()->create();
    $chantier = Chantier::factory()->create(['manager_id' => $manager->id]);

    expect($chantier->manager)->toBeInstanceOf(Employee::class)
        ->and($chantier->manager->id)->toBe($manager->id);
});

it('has members relation', function () {
    $chantier = Chantier::factory()->create();
    $employee = Employee::factory()->create();

    $chantier->members()->attach($employee->id);

    expect($chantier->members)->toHaveCount(1)
        ->and($chantier->members->first()->id)->toBe($employee->id);
});

it('has subcontractors relation', function () {
    $chantier = Chantier::factory()->create();
    $subcontractor = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);

    $chantier->subcontractors()->attach($subcontractor->id);

    expect($chantier->subcontractors)->toHaveCount(1)
        ->and($chantier->subcontractors->first()->id)->toBe($subcontractor->id);
});

it('has phases relation', function () {
    $chantier = Chantier::factory()->create();
    ChantierPhase::factory()->create(['chantier_id' => $chantier->id]);

    expect($chantier->phases)->toHaveCount(5)
        ->and($chantier->phases->first())->toBeInstanceOf(ChantierPhase::class);
});

it('has logs relation', function () {
    $chantier = Chantier::factory()->create();
    ChantierLog::factory()->create(['chantier_id' => $chantier->id]);

    expect($chantier->logs)->toHaveCount(1)
        ->and($chantier->logs->first())->toBeInstanceOf(ChantierLog::class);
});

it('has timeEntries relation', function () {
    $chantier = Chantier::factory()->create();
    TimeEntry::factory()->create(['chantier_id' => $chantier->id]);

    expect($chantier->timeEntries)->toHaveCount(1)
        ->and($chantier->timeEntries->first())->toBeInstanceOf(TimeEntry::class);
});

it('has doeDocuments relation', function () {
    $chantier = Chantier::factory()->create();
    $doc = new DoeDocument;
    $doc->forceFill([
        'chantier_id' => $chantier->id,
        'name' => 'Test Doc',
        'category' => DoeDocumentCategory::PLAN,
        'is_validated' => true,
    ])->save();

    expect($chantier->doeDocuments)->toHaveCount(1)
        ->and($chantier->doeDocuments->first())->toBeInstanceOf(DoeDocument::class);
});

it('has weatherAlerts relation', function () {
    $chantier = Chantier::factory()->create();
    $alert = new WeatherAlert;
    $alert->forceFill([
        'chantier_id' => $chantier->id,
        'started_at' => now(),
        'ended_at' => now()->addDay(),
        'type' => 'rain',
        'severity' => 'high',
        'description' => 'Heavy rain',
    ])->save();

    expect($chantier->weatherAlerts)->toHaveCount(1)
        ->and($chantier->weatherAlerts->first())->toBeInstanceOf(WeatherAlert::class);
});

it('has cibtpDeclarations relation', function () {
    $chantier = Chantier::factory()->create();
    $cibtp = new CibtpDeclaration;
    $cibtp->forceFill([
        'chantier_id' => $chantier->id,
        'date' => now(),
        'status' => 'draft',
        'total_lost_hours' => 8.5,
    ])->save();

    expect($chantier->cibtpDeclarations)->toHaveCount(1)
        ->and($chantier->cibtpDeclarations->first())->toBeInstanceOf(CibtpDeclaration::class);
});

it('has reserves relation', function () {
    $chantier = Chantier::factory()->create();
    ChantierReserve::factory()->create(['chantier_id' => $chantier->id]);

    expect($chantier->reserves)->toHaveCount(1)
        ->and($chantier->reserves->first())->toBeInstanceOf(ChantierReserve::class);
});

it('has bankTransactions relation', function () {
    $chantier = Chantier::factory()->create();
    BankTransaction::factory()->create(['chantier_id' => $chantier->id]);

    expect($chantier->bankTransactions)->toHaveCount(1)
        ->and($chantier->bankTransactions->first())->toBeInstanceOf(BankTransaction::class);
});
