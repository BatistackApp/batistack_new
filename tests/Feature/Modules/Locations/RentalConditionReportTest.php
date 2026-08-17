<?php

use App\Enums\Locations\RentalConditionReportType;
use App\Enums\Locations\RentalStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Chantiers\Chantier;
use App\Models\Locations\RentalConditionReport;
use App\Models\Locations\RentalContract;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use App\Services\Locations\RentalConditionReportService;

function makeManagedScenario(): array
{
    $employee = Employee::factory()->create();
    $user = $employee->user;
    $chantier = Chantier::factory()->create(['manager_id' => $employee->id]);
    $contract = RentalContract::factory()->create([
        'chantier_id' => $chantier->id,
        'supplier_id' => ThirdParty::factory()->state(['type' => ThirdPartyType::SUPPLIER]),
        'status' => RentalStatus::ACTIVE,
    ]);

    return compact('employee', 'user', 'chantier', 'contract');
}

it('creates a reception report idempotently with server-side timestamp', function () {
    ['user' => $user, 'contract' => $contract] = makeManagedScenario();
    $service = app(RentalConditionReportService::class);

    $payload = [
        'contract_id' => $contract->id,
        'type' => RentalConditionReportType::RECEPTION->value,
        'client_key' => 'ck-reception-1',
        'comment' => 'Matériel conforme, une rayure sur la pelle.',
        'latitude' => 43.296482,
        'longitude' => 5.369780,
    ];

    $report = $service->createFromSync($user, $payload);
    $again = $service->createFromSync($user, $payload);

    expect($report)->not->toBeNull()
        ->and($report->rental_contract_id)->toBe($contract->id)
        ->and($report->type)->toBe(RentalConditionReportType::RECEPTION)
        ->and($report->captured_at)->not->toBeNull()
        ->and($report->client_key)->toBe('ck-reception-1')
        ->and($again->id)->toBe($report->id)
        ->and(RentalConditionReport::count())->toBe(1);
});

it('rejects a contract not managed by the user', function () {
    $otherEmployee = Employee::factory()->create();
    $otherChantier = Chantier::factory()->create(['manager_id' => $otherEmployee->id]);
    $contract = RentalContract::factory()->create([
        'chantier_id' => $otherChantier->id,
        'supplier_id' => ThirdParty::factory()->state(['type' => ThirdPartyType::SUPPLIER]),
        'status' => RentalStatus::ACTIVE,
    ]);

    ['user' => $user] = makeManagedScenario();
    $service = app(RentalConditionReportService::class);

    $report = $service->createFromSync($user, [
        'contract_id' => $contract->id,
        'type' => RentalConditionReportType::RESTITUTION->value,
        'client_key' => 'ck-unauthorized-1',
    ]);

    expect($report)->toBeNull();
});

it('rejects an invalid type or missing client key', function () {
    ['user' => $user, 'contract' => $contract] = makeManagedScenario();
    $service = app(RentalConditionReportService::class);

    expect($service->createFromSync($user, [
        'contract_id' => $contract->id,
        'type' => 'invalid',
        'client_key' => 'ck-bad-1',
    ]))->toBeNull();

    expect($service->createFromSync($user, [
        'contract_id' => $contract->id,
        'type' => RentalConditionReportType::RECEPTION->value,
        'client_key' => '',
    ]))->toBeNull();
});

it('signs the report when a signature is provided', function () {
    ['user' => $user, 'contract' => $contract] = makeManagedScenario();
    $service = app(RentalConditionReportService::class);

    $report = $service->createFromSync($user, [
        'contract_id' => $contract->id,
        'type' => RentalConditionReportType::RESTITUTION->value,
        'client_key' => 'ck-signed-1',
        'signature' => 'base64-signature-data',
    ]);

    expect($report->isSigned())->toBeTrue()
        ->and($report->signed_at)->not->toBeNull()
        ->and($report->signature_checksum)->toBe(hash('sha256', 'base64-signature-data'));
});

it('attaches a photo to the report media collection', function () {
    ['user' => $user, 'contract' => $contract] = makeManagedScenario();
    $service = app(RentalConditionReportService::class);

    $report = $service->createFromSync($user, [
        'contract_id' => $contract->id,
        'type' => RentalConditionReportType::RECEPTION->value,
        'client_key' => 'ck-photo-1',
    ]);

    $service->attachPhoto($report, base64_encode('fake-image-bytes'));

    expect($report->getMedia('photos'))->toHaveCount(1);
});

it('exposes managed contracts through the sync API index', function () {
    ['user' => $user, 'contract' => $contract] = makeManagedScenario();

    $response = $this->actingAs($user)->getJson(route('etat-des-lieux.api.contracts'));

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

it('processes sync operations end to end', function () {
    ['user' => $user, 'contract' => $contract] = makeManagedScenario();

    $response = $this->actingAs($user)->postJson(route('etat-des-lieux.api.sync'), [
        'operations' => [
            [
                'type' => 'CREATE_REPORT',
                'payload' => [
                    'contract_id' => $contract->id,
                    'type' => RentalConditionReportType::RECEPTION->value,
                    'client_key' => 'ck-e2e-1',
                ],
            ],
            [
                'type' => 'UPLOAD_PHOTO',
                'payload' => [
                    'report_key' => 'ck-e2e-1',
                    'image' => base64_encode('fake-image-bytes'),
                ],
            ],
        ],
    ]);

    $response->assertOk()
        ->assertJson(['success' => true, 'processed' => 2, 'failed' => 0]);

    $report = RentalConditionReport::where('client_key', 'ck-e2e-1')->first();
    expect($report)->not->toBeNull()
        ->and($report->getMedia('photos'))->toHaveCount(1);
});
