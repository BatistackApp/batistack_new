<?php

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Enums\Interventions\MaintenanceContractFrequency;
use App\Enums\Interventions\MaintenanceContractStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Chantiers\Chantier;
use App\Models\Core\Company;
use App\Models\Interventions\ClientEquipment;
use App\Models\Interventions\Intervention;
use App\Models\Interventions\MaintenanceContract;
use App\Models\Interventions\MaintenanceContractReminder;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Notifications\Interventions\MaintenanceContractReminderNotification;
use App\Services\Interventions\MaintenanceContractService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->client = ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]);
    $this->equipment = ClientEquipment::factory()->create(['third_party_id' => $this->client->id]);
});

function makeContract(array $overrides = []): MaintenanceContract
{
    $base = [
        'company_id' => test()->company->id,
        'third_party_id' => test()->client->id,
        'client_equipment_id' => test()->equipment->id,
        'name' => 'Contrat test',
        'frequency' => MaintenanceContractFrequency::MONTHLY,
        'status' => MaintenanceContractStatus::ACTIVE,
        'start_date' => now()->subMonths(2)->toDateString(),
        'next_due_date' => now()->subDay()->toDateString(),
        'flat_rate_price' => 199.0,
    ];

    return MaintenanceContract::factory()->create(array_merge($base, $overrides));
}

describe('MaintenanceContract', function () {
    test('reference is generated on creating', function () {
        $contract = makeContract(['reference' => null]);

        expect($contract->reference)->toMatch('/^MC-\d{4}-\d{4}$/');
    });

    test('keeps an explicit reference on creation', function () {
        $contract = makeContract(['reference' => 'MC-2026-9999']);

        expect($contract->reference)->toBe('MC-2026-9999');
    });

    test('status badges are enumerable', function () {
        expect(MaintenanceContractStatus::cases())->not->toBeEmpty();
        expect(MaintenanceContractFrequency::MONTHLY->getLabel())->not->toBeEmpty();
    });

    test('belongs to client equipment and third party', function () {
        $contract = makeContract();

        expect($contract->thirdParty->is($this->client))->toBeTrue();
        expect($contract->clientEquipment->is($this->equipment))->toBeTrue();
        expect($contract->interventions()->count())->toBe(0);
    });

    test('factory links the equipment to the same third party as the contract', function () {
        $contract = MaintenanceContract::factory()->create();

        expect($contract->clientEquipment->third_party_id)->toBe($contract->third_party_id)
            ->and($contract->clientEquipment->company_id)->toBe($contract->company_id);
    });

    test('exposes the company, chantier and reminders relations', function () {
        $chantier = Chantier::factory()->create();
        $contract = makeContract(['chantier_id' => $chantier->id]);

        expect($contract->company->is($this->company))->toBeTrue();
        expect($contract->chantier->is($chantier))->toBeTrue();
        expect($contract->reminders()->count())->toBe(0);
    });

    test('exposes the signatures morph relation', function () {
        $contract = makeContract();
        $user = User::factory()->create();

        $contract->signatures()->create([
            'user_id' => $user->id,
            'token' => 'sig-token',
            'checksum' => 'abc123',
            'status' => SignatureStatus::PENDING,
            'type' => SignatureType::AUTOGRAPH,
        ]);

        expect($contract->signatures()->count())->toBe(1);
        expect($contract->signatures()->first()->signable->is($contract))->toBeTrue();
    });

    test('reminders belong to their contract', function () {
        $contract = makeContract();

        $reminder = MaintenanceContractReminder::create([
            'contract_id' => $contract->id,
            'due_date' => $contract->next_due_date?->toDateString() ?? now()->toDateString(),
            'days_before' => 15,
            'sent_at' => now(),
        ]);

        expect($reminder->contract->is($contract))->toBeTrue();
    });

    test('generated interventions link back to their maintenance contract', function () {
        $contract = makeContract();

        app(MaintenanceContractService::class)->generateDueInterventions();

        $intervention = Intervention::where('maintenance_contract_id', $contract->id)->first();

        expect($intervention->maintenanceContract->is($contract))->toBeTrue();
    });
});

describe('MaintenanceContractService', function () {
    test('generateDueInterventions creates a planned intervention for due contracts', function () {
        $contract = makeContract();

        $count = app(MaintenanceContractService::class)->generateDueInterventions();

        expect($count)->toBe(1);
        $contract->refresh();

        $intervention = Intervention::where('maintenance_contract_id', $contract->id)->first();
        expect($intervention)->not->toBeNull();
        expect($intervention->type)->toBe(InterventionType::FORFAIT);
        expect($intervention->status)->toBe(InterventionStatus::PLANIFIEE);
        expect($intervention->flat_rate_price)->toBe('199.00');
        expect($intervention->third_party_id)->toBe($this->client->id);
        expect($intervention->client_equipment_id)->toBe($this->equipment->id);

        expect($contract->last_generated_at)->not->toBeNull();
        expect($contract->next_due_date)->not->toBeNull();
    });

    test('generateDueInterventions is idempotent: no duplicate when not yet due', function () {
        $contract = makeContract(['next_due_date' => now()->addMonth()->toDateString()]);

        $count = app(MaintenanceContractService::class)->generateDueInterventions();

        expect($count)->toBe(0);
        expect(Intervention::where('maintenance_contract_id', $contract->id)->count())->toBe(0);
    });

    test('next due date advances according to frequency', function () {
        $contract = makeContract(['frequency' => MaintenanceContractFrequency::QUARTERLY, 'next_due_date' => now()->toDateString()]);

        app(MaintenanceContractService::class)->generateDueInterventions();

        $contract->refresh();
        expect($contract->next_due_date?->format('Y-m-d'))->toBe(now()->addMonths(3)->format('Y-m-d'));
    });

    test('contract becomes completed when next due date exceeds end date', function () {
        $contract = makeContract([
            'end_date' => now()->addDay()->toDateString(),
            'next_due_date' => now()->toDateString(),
        ]);

        app(MaintenanceContractService::class)->generateDueInterventions();

        $contract->refresh();
        expect($contract->status)->toBe(MaintenanceContractStatus::COMPLETED);
        expect($contract->next_due_date)->toBeNull();
    });

    test('paused contracts are skipped', function () {
        $contract = makeContract(['status' => MaintenanceContractStatus::PAUSED]);

        $count = app(MaintenanceContractService::class)->generateDueInterventions();

        expect($count)->toBe(0);
        expect(Intervention::where('maintenance_contract_id', $contract->id)->count())->toBe(0);
    });

    test('generateForContract with force generates even if not due', function () {
        $contract = makeContract(['next_due_date' => now()->addMonth()->toDateString()]);

        $created = app(MaintenanceContractService::class)->generateForContract($contract, force: true);

        expect($created)->toBeTrue();
        expect(Intervention::where('maintenance_contract_id', $contract->id)->count())->toBe(1);
    });

    test('notifyUpcoming sends one reminder per threshold and deduplicates', function () {
        Notification::fake();

        $contract = makeContract(['next_due_date' => now()->addDays(10)->toDateString()]);

        $count = app(MaintenanceContractService::class)->notifyUpcoming();

        // J-30, J-15 (dus), J-7 (pas encore)
        expect($count)->toBe(2);

        $count2 = app(MaintenanceContractService::class)->notifyUpcoming();
        expect($count2)->toBe(0);

        Notification::assertSentOnDemand(MaintenanceContractReminderNotification::class, 2);
        expect(MaintenanceContractReminder::where('contract_id', $contract->id)->count())->toBe(2);
    });

    test('reminders are sent to the primary contact email', function () {
        Notification::fake();

        $this->client->update(['email' => null]);
        $contact = Contact::factory()->create([
            'third_party_id' => $this->client->id,
            'is_primary' => true,
            'email' => 'contact@batistack.fr',
        ]);

        makeContract(['next_due_date' => now()->addDays(10)->toDateString()]);

        app(MaintenanceContractService::class)->notifyUpcoming();

        Notification::assertSentOnDemand(MaintenanceContractReminderNotification::class);
    });

    test('reminders fall back to the third party email when no contact exists', function () {
        Notification::fake();

        $this->client->update(['email' => 'client@batistack.fr']);

        makeContract(['next_due_date' => now()->addDays(10)->toDateString()]);

        app(MaintenanceContractService::class)->notifyUpcoming();

        Notification::assertSentOnDemand(MaintenanceContractReminderNotification::class);
    });

    test('no reminder is sent when the client has no reachable email', function () {
        Notification::fake();

        Contact::where('third_party_id', $this->client->id)->delete();
        $this->client->update(['email' => null]);

        makeContract(['next_due_date' => now()->addDays(10)->toDateString()]);

        $count = app(MaintenanceContractService::class)->notifyUpcoming();

        expect($count)->toBe(0);
        Notification::assertNothingSent();
    });

    test('reminder is deleted and the error is rethrown when sending fails', function () {
        $manager = Mockery::mock('Illuminate\Notifications\NotificationManager')->makePartial();
        $manager->shouldReceive('send')->andThrow(new RuntimeException('SMTP indisponible'));
        Notification::swap($manager);

        $this->client->update(['email' => 'client@batistack.fr']);
        makeContract(['next_due_date' => now()->addDays(10)->toDateString()]);

        expect(fn () => app(MaintenanceContractService::class)->notifyUpcoming())
            ->toThrow(RuntimeException::class, 'SMTP indisponible');

        expect(MaintenanceContractReminder::count())->toBe(0);
    });

    test('generateForContract returns false when the contract no longer exists', function () {
        $contract = makeContract();
        $contract->delete();

        $created = app(MaintenanceContractService::class)->generateForContract($contract);

        expect($created)->toBeFalse();
    });

    test('generateForContract returns false for non-active contracts', function () {
        $contract = makeContract(['status' => MaintenanceContractStatus::PAUSED]);

        $created = app(MaintenanceContractService::class)->generateForContract($contract);

        expect($created)->toBeFalse();
    });

    test('generateForContract returns false when not due and not forced', function () {
        $contract = makeContract(['next_due_date' => now()->addMonth()->toDateString()]);

        $created = app(MaintenanceContractService::class)->generateForContract($contract);

        expect($created)->toBeFalse();
    });

    test('generateDueInterventions logs and continues when a contract fails', function () {
        Log::spy();

        $contract = makeContract();

        $service = Mockery::mock(MaintenanceContractService::class)->makePartial();
        $service->shouldReceive('generateForContract')->once()->andThrow(new RuntimeException('boom'));

        $count = $service->generateDueInterventions();

        expect($count)->toBe(0);
        Log::shouldHaveReceived('warning')->once();
    });
});

describe('MaintenanceContractObserver', function () {
    test('deleted, restored and forceDeleted are handled without errors', function () {
        $contract = makeContract();

        $contract->delete();
        $contract->restore();
        $contract->forceDelete();

        expect(MaintenanceContract::find($contract->id))->toBeNull();
    });
});
