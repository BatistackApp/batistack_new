<?php

use App\Enums\Locations\RentalStatus;
use App\Models\Locations\RentalContract;
use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use App\Models\User;
use App\Models\Tiers\ThirdParty;
use App\Models\Tiers\Contact;
use App\Notifications\Tiers\WelcomeCustomerNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

function createTestEnvironment(): array
{
    Notification::fake([WelcomeCustomerNotification::class]);
    
    $managerUser = User::factory()->create(['email' => 'manager-' . uniqid() . '@test.com', 'name' => 'Manager Test']);
    $manager = Employee::factory()->create(['user_id' => $managerUser->id]);
    $chantier = Chantier::factory()->create(['manager_id' => $managerUser->id]);
    
    $supplier = ThirdParty::factory()->create(['name' => 'Fournisseur Test']);
    $contact = Contact::factory()->create([
        'email' => 'fournisseur-' . uniqid() . '@test.com',
        'third_party_id' => $supplier->id,
    ]);
    $contact->refresh();
    $supplierUser = $contact->user;
    
    return compact('managerUser', 'manager', 'chantier', 'supplier', 'contact', 'supplierUser');
}

it('applique les pénalités et passe le statut à OVERDUE pour les contrats en dépassement', function () {
    Notification::fake();
    
    $env = createTestEnvironment();
    
    $contract = RentalContract::factory()->create([
        'supplier_id' => $env['supplier']->id,
        'chantier_id' => $env['chantier']->id,
        'reference' => 'LOC-OVERDUE-001',
        'status' => RentalStatus::ACTIVE,
        'expected_end_date' => Carbon::yesterday(),
        'daily_penalty_rate' => 50,
        'penalty_amount' => 0,
    ]);
    
    $exitCode = Artisan::call('rentals:check-overages');
    
    expect($exitCode)->toBe(0);
    
    $contract->refresh();
    expect($contract->status)->toBe(RentalStatus::OVERDUE);
    expect((float) $contract->penalty_amount)->toBe(50.0);
});

it('envoie une notification au manager pour les pénalités de dépassement', function () {
    Notification::fake();
    
    $env = createTestEnvironment();
    
    $contract = RentalContract::factory()->create([
        'supplier_id' => $env['supplier']->id,
        'chantier_id' => $env['chantier']->id,
        'reference' => 'LOC-OVERDUE-002',
        'status' => RentalStatus::ACTIVE,
        'expected_end_date' => Carbon::yesterday(),
        'daily_penalty_rate' => 50,
        'penalty_amount' => 0,
    ]);
    
    Artisan::call('rentals:check-overages');
    
    Notification::assertSentTo(
        $env['managerUser'],
        \App\Notifications\RentalOverageAlert::class,
        function ($notification) use ($contract) {
            expect($notification->contract->id)->toBe($contract->id);
            expect($notification->daysOverdue)->toBe(1);
            expect($notification->penaltyAmount)->toBe(50.0);
            expect($notification->totalPenaltyAmount)->toBe(50.0);
            return true;
        }
    );
});

it('envoie une alerte J-1 pour les contrats finissant demain', function () {
    Notification::fake();
    
    $env = createTestEnvironment();
    
    $endingContract = RentalContract::factory()->create([
        'supplier_id' => $env['supplier']->id,
        'chantier_id' => $env['chantier']->id,
        'reference' => 'LOC-ENDING-002',
        'status' => RentalStatus::ACTIVE,
        'expected_end_date' => Carbon::tomorrow(),
        'daily_penalty_rate' => 30,
    ]);
    
    Artisan::call('rentals:check-overages');
    
    Notification::assertSentTo(
        $env['managerUser'],
        \App\Notifications\RentalExpirationAlert::class,
        function ($notification) use ($endingContract) {
            expect($notification->contract->id)->toBe($endingContract->id);
            expect($notification->daysUntilExpiration)->toBe(1);
            return true;
        }
    );
});

it('cumule les pénalités sur plusieurs jours', function () {
    Notification::fake();
    
    $env = createTestEnvironment();
    
    $oldContract = RentalContract::factory()->create([
        'supplier_id' => $env['supplier']->id,
        'chantier_id' => $env['chantier']->id,
        'reference' => 'LOC-OLD-003',
        'status' => RentalStatus::ACTIVE,
        'expected_end_date' => Carbon::today()->subDays(5),
        'daily_penalty_rate' => 40,
        'penalty_amount' => 200,
    ]);
    
    Artisan::call('rentals:check-overages');
    
    $oldContract->refresh();
    expect((float) $oldContract->penalty_amount)->toBe(400.0);
    expect($oldContract->status)->toBe(RentalStatus::OVERDUE);
});

it('ne fait rien si pas de daily_penalty_rate', function () {
    Notification::fake();
    
    $env = createTestEnvironment();
    
    $noPenaltyContract = RentalContract::factory()->create([
        'supplier_id' => $env['supplier']->id,
        'chantier_id' => $env['chantier']->id,
        'reference' => 'LOC-NO-PENALTY',
        'status' => RentalStatus::ACTIVE,
        'expected_end_date' => Carbon::yesterday(),
        'daily_penalty_rate' => null,
    ]);
    
    Artisan::call('rentals:check-overages');
    
    $noPenaltyContract->refresh();
    expect($noPenaltyContract->status)->toBe(RentalStatus::ACTIVE);
    expect((float) $noPenaltyContract->penalty_amount)->toBe(0.0);
});

it('notifie le manager en plus du fournisseur pour les pénalités', function () {
    Notification::fake();
    
    $env = createTestEnvironment();
    
    $contract = RentalContract::factory()->create([
        'supplier_id' => $env['supplier']->id,
        'chantier_id' => $env['chantier']->id,
        'reference' => 'LOC-MGR-NOTIF',
        'status' => RentalStatus::ACTIVE,
        'expected_end_date' => Carbon::yesterday(),
        'daily_penalty_rate' => 100,
        'penalty_amount' => 0,
    ]);
    
    Artisan::call('rentals:check-overages');
    
    Notification::assertSentTo(
        $env['managerUser'],
        \App\Notifications\RentalOverageAlert::class,
        function ($notification) use ($contract) {
            expect($notification->contract->id)->toBe($contract->id)
                ->and($notification->penaltyAmount)->toBe(100.0)
                ->and($notification->totalPenaltyAmount)->toBe(100.0);
            return true;
        }
    );
});

it('n\'envoie pas d\'alerte J-1 quand il n\'y a pas de manager', function () {
    Notification::fake();
    
    $env = createTestEnvironment();
    
    $chantierNoManager = Chantier::factory()->create(['manager_id' => null]);
    
    $contract = RentalContract::factory()->create([
        'supplier_id' => $env['supplier']->id,
        'chantier_id' => $chantierNoManager->id,
        'reference' => 'LOC-NO-MGR-END',
        'status' => RentalStatus::ACTIVE,
        'expected_end_date' => Carbon::tomorrow(),
        'daily_penalty_rate' => 30,
    ]);
    
    Artisan::call('rentals:check-overages');
    
    Notification::assertNotSentTo(
        $env['managerUser'],
        \App\Notifications\RentalExpirationAlert::class
    );
});

it('applique les pénalités sans notifier le manager quand il n\'y en a pas', function () {
    Notification::fake();
    
    $env = createTestEnvironment();
    
    $chantierNoManager = Chantier::factory()->create(['manager_id' => null]);
    
    $contract = RentalContract::factory()->create([
        'supplier_id' => $env['supplier']->id,
        'chantier_id' => $chantierNoManager->id,
        'reference' => 'LOC-NO-MGR-PENALTY',
        'status' => RentalStatus::ACTIVE,
        'expected_end_date' => Carbon::yesterday(),
        'daily_penalty_rate' => 75,
        'penalty_amount' => 0,
    ]);
    
    Artisan::call('rentals:check-overages');
    
    $contract->refresh();
    expect($contract->status)->toBe(RentalStatus::OVERDUE)
        ->and((float) $contract->penalty_amount)->toBe(75.0);
    
    Notification::assertNotSentTo(
        $env['managerUser'],
        \App\Notifications\RentalOverageAlert::class
    );
});

it('notifie correctement les contrats avec pénalités cumulées', function () {
    Notification::fake();
    
    $env = createTestEnvironment();
    
    $contract = RentalContract::factory()->create([
        'supplier_id' => $env['supplier']->id,
        'chantier_id' => $env['chantier']->id,
        'reference' => 'LOC-CUMUL-004',
        'status' => RentalStatus::ACTIVE,
        'expected_end_date' => Carbon::today()->subDays(3),
        'daily_penalty_rate' => 20,
        'penalty_amount' => 10,
    ]);
    
    Artisan::call('rentals:check-overages');
    
    $contract->refresh();
    expect((float) $contract->penalty_amount)->toBe(70.0)
        ->and($contract->status)->toBe(RentalStatus::OVERDUE);
    
    Notification::assertSentTo(
        $env['managerUser'],
        \App\Notifications\RentalOverageAlert::class,
        function ($notification) use ($contract) {
            expect($notification->penaltyAmount)->toBe(60.0)
                ->and($notification->totalPenaltyAmount)->toBe(70.0);
            return true;
        }
    );
});
