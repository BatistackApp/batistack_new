<?php

use App\Enums\Locations\RentalConditionReportType;
use App\Enums\Locations\RentalStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Chantiers\Chantier;
use App\Models\Locations\RentalConditionReport;
use App\Models\Locations\RentalContract;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
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

it('returns an empty list from the index when the user has no employee record', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson(route('etat-des-lieux.api.contracts'))
        ->assertOk()
        ->assertJson(['data' => []]);
});

it('indexes contracts when the user is a chantier member (not manager)', function () {
    $member = Employee::factory()->create();
    $chantier = Chantier::factory()->create();
    $chantier->members()->attach($member->id);

    RentalContract::factory()->create([
        'chantier_id' => $chantier->id,
        'supplier_id' => ThirdParty::factory()->state(['type' => ThirdPartyType::SUPPLIER]),
        'status' => RentalStatus::ACTIVE,
    ]);

    $this->actingAs($member->user)->getJson(route('etat-des-lieux.api.contracts'))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('rejects sync for a user without an employee record', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('etat-des-lieux.api.sync'), ['operations' => []])
        ->assertStatus(403);
});

it('counts failures for unsupported sync operation types', function () {
    ['user' => $user] = makeManagedScenario();

    $this->actingAs($user)->postJson(route('etat-des-lieux.api.sync'), [
        'operations' => [['type' => 'FOO', 'payload' => []]],
    ])->assertOk()->assertJson(['success' => true, 'processed' => 0, 'failed' => 1]);
});

it('counts failures when a create report is rejected', function () {
    ['user' => $user] = makeManagedScenario();

    $this->actingAs($user)->postJson(route('etat-des-lieux.api.sync'), [
        'operations' => [[
            'type' => 'CREATE_REPORT',
            'payload' => [
                'contract_id' => 999999,
                'type' => RentalConditionReportType::RECEPTION->value,
                'client_key' => 'ck-rejected',
            ],
        ]],
    ])->assertOk()->assertJson(['success' => true, 'processed' => 0, 'failed' => 1]);
});

it('counts failures when uploading a photo without a matching report or image', function () {
    ['user' => $user, 'contract' => $contract] = makeManagedScenario();
    $service = app(RentalConditionReportService::class);
    $service->createFromSync($user, [
        'contract_id' => $contract->id,
        'type' => RentalConditionReportType::RECEPTION->value,
        'client_key' => 'ck-img',
    ]);

    $this->actingAs($user)->postJson(route('etat-des-lieux.api.sync'), [
        'operations' => [
            ['type' => 'UPLOAD_PHOTO', 'payload' => ['report_key' => 'ck-nonexistent']],
            ['type' => 'UPLOAD_PHOTO', 'payload' => ['report_key' => 'ck-img', 'image' => '']],
        ],
    ])->assertOk()->assertJson(['success' => true, 'processed' => 0, 'failed' => 2]);
});

it('returns 500 and rolls back when a sync operation throws', function () {
    ['user' => $user, 'contract' => $contract] = makeManagedScenario();

    $service = Mockery::mock(RentalConditionReportService::class);
    $service->shouldReceive('createFromSync')->once()->andThrow(new Exception('boom'));
    app()->instance(RentalConditionReportService::class, $service);

    $this->actingAs($user)->postJson(route('etat-des-lieux.api.sync'), [
        'operations' => [['type' => 'CREATE_REPORT', 'payload' => ['contract_id' => $contract->id]]],
    ])->assertStatus(500)->assertJson(['success' => false]);
});

it('authorizes a chantier member and denies a user without an employee record', function () {
    $member = Employee::factory()->create();
    $chantier = Chantier::factory()->create();
    $chantier->members()->attach($member->id);
    $contract = RentalContract::factory()->create([
        'chantier_id' => $chantier->id,
        'supplier_id' => ThirdParty::factory()->state(['type' => ThirdPartyType::SUPPLIER]),
        'status' => RentalStatus::ACTIVE,
    ]);

    $service = app(RentalConditionReportService::class);
    expect($service->userManagesContract($member->user, $contract->id))->toBeTrue()
        ->and($service->userManagesContract(User::factory()->create(), $contract->id))->toBeFalse();
});

it('logs and ignores failures when attaching a photo', function () {
    $report = Mockery::mock(RentalConditionReport::class)->makePartial();
    $report->shouldReceive('addMediaFromBase64')->once()->andThrow(new Exception('invalid image'));

    $service = app(RentalConditionReportService::class);
    $service->attachPhoto($report, 'not-a-valid-image');

    expect(true)->toBeTrue();
});

it('exposes report scopes and helper methods', function () {
    ['user' => $user, 'contract' => $contract] = makeManagedScenario();
    $service = app(RentalConditionReportService::class);
    $service->createFromSync($user, [
        'contract_id' => $contract->id,
        'type' => RentalConditionReportType::RECEPTION->value,
        'client_key' => 'ck-scope-r',
    ]);
    $service->createFromSync($user, [
        'contract_id' => $contract->id,
        'type' => RentalConditionReportType::RESTITUTION->value,
        'client_key' => 'ck-scope-t',
        'signature' => 'sig',
    ]);

    expect(RentalConditionReport::reception()->count())->toBe(1)
        ->and(RentalConditionReport::restitution()->count())->toBe(1)
        ->and(RentalConditionReport::signed()->count())->toBe(1)
        ->and(RentalConditionReport::byContract($contract->id)->count())->toBe(2);

    RentalConditionReport::withPhotos()->get();

    $reception = RentalConditionReport::where('client_key', 'ck-scope-r')->first();
    expect($reception->isReception())->toBeTrue()
        ->and($reception->isRestitution())->toBeFalse()
        ->and($reception->getTypeLabel())->toBe('Réception')
        ->and($reception->getPhotoCount())->toBe(0)
        ->and($reception->getDisplayName())->toContain('Réception');
});

it('falls back to created_at in the display name when not captured', function () {
    $report = RentalConditionReport::factory()->create(['captured_at' => null]);

    expect($report->getDisplayName())->toContain($report->created_at->format('d/m/Y H:i'));
});

it('maps report types to labels and colors', function () {
    expect(RentalConditionReportType::RECEPTION->getLabel())->toBe('Réception')
        ->and(RentalConditionReportType::RESTITUTION->getLabel())->toBe('Restitution')
        ->and(RentalConditionReportType::RECEPTION->getColor())->toBe('success')
        ->and(RentalConditionReportType::RESTITUTION->getColor())->toBe('danger');
});

it('lists condition reports through the contract relation', function () {
    ['user' => $user, 'contract' => $contract] = makeManagedScenario();
    $service = app(RentalConditionReportService::class);
    $service->createFromSync($user, [
        'contract_id' => $contract->id,
        'type' => RentalConditionReportType::RECEPTION->value,
        'client_key' => 'ck-rel',
    ]);

    expect($contract->conditionReports()->count())->toBe(1);

    $report = RentalConditionReport::where('client_key', 'ck-rel')->first();
    expect($report->contract()->count())->toBe(1);
});
