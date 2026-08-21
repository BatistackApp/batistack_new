<?php

use App\Enums\Locations\RentalStatus;
use App\Models\Locations\RentalContract;
use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use App\Models\User;
use App\Models\Tiers\ThirdParty;
use App\Models\Tiers\Contact;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

function uniqueEmail(string $prefix): string
{
    return "{$prefix}-" . uniqid() . '@test.com';
}

function createTestEnvironment(): array
{
    $managerUser = User::factory()->create(['email' => 'manager-' . uniqid() . '@test.com', 'name' => 'Manager Test']);
    $manager = Employee::factory()->create(['user_id' => $managerUser->id]);
    $chantier = Chantier::factory()->create(['manager_id' => $managerUser->id]);
    
    $supplier = ThirdParty::factory()->create(['name' => 'Fournisseur Test']);
    $contact = Contact::factory()->create(['email' => 'fournisseur-' . uniqid() . '@test.com']);
    $supplier = ThirdParty::where('id', $supplier->id)->first();
    $supplier->update(['contact_id' => $contact->id]);
    $supplierUser = User::factory()->create(['email' => $contact->email, 'name' => 'Fournisseur Contact']);
    $contact->update(['user_id' => $supplierUser->id]);
    
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
    expect($contract->penalty_amount)->toBe(50);
});

it('envoie une notification au manager et au fournisseur', function () {
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
    
    Artisan::call('rentals:check-overages');
    
    Notification::assertSentTo(
        $env['supplierUser'],
        \App\Notifications\RentalOverageAlert::class,
        function ($notification) use ($contract) {
            expect($notification->contract->id)->toBe($contract->id);
            expect($notification->daysOverdue)->toBe(1);
            expect($notification->penaltyAmount)->toBe(50);
            expect($notification->totalPenaltyAmount)->toBe(50);
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
        'status' => RentalStatus::OVERDUE,
        'expected_end_date' => Carbon::today()->subDays(5),
        'daily_penalty_rate' => 40,
        'penalty_amount' => 200,
    ]);
    
    Artisan::call('rentals:check-overages');
    
    $oldContract->refresh();
    expect($oldContract->penalty_amount)->toBe(400);
    expect($oldContract->status)->toBe(RentalStatus::OVERDUE);
});

it('ne fait rien si pas de daily_penalty_rate', function () {
    Notification::fake();
    
    $supplier4 = ThirdParty::factory()->create(['name' => 'Fournisseur 4']);
    $contact4 = Contact::factory()->create(['email' => 'fournisseur4-' . uniqid() . '@test.com']);
    $supplier4 = ThirdParty::where('id', $supplier4->id)->first();
    $supplier4->update(['contact_id' => $contact4->id]);
    User::factory()->create(['email' => $contact4->email, 'name' => 'Fournisseur 4 Contact']);
    $contact4->update(['user_id' => User::where('email', $contact4->email)->first()->id]);
    
    $noPenaltyContract = RentalContract::factory()->create([
        'supplier_id' => $supplier4->id,
        'chantier_id' => Chantier::factory()->create()->id,
        'reference' => 'LOC-NO-PENALTY',
        'status' => RentalStatus::ACTIVE,
        'expected_end_date' => Carbon::yesterday(),
        'daily_penalty_rate' => null,
    ]);
    
    Artisan::call('rentals:check-overages');
    
    $noPenaltyContract->refresh();
    expect($noPenaltyContract->status)->toBe(RentalStatus::ACTIVE);
    expect($noPenaltyContract->penalty_amount)->toBe(0);
    
    Notification::assertNothingSent();
});