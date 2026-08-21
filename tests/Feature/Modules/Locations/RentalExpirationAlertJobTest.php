<?php

use App\Enums\Locations\RentalStatus;
use App\Models\Locations\RentalContract;
use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use App\Models\User;
use App\Models\Tiers\ThirdParty;
use App\Jobs\Locations\RentalExpirationAlertJob;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

beforeEach(function () {
    Notification::fake();
    
    // Créer un seul manager pour tous les tests
    $this->managerUser = User::factory()->create(['email' => 'manager-' . uniqid() . '@test.com', 'name' => 'Manager Test']);
    $this->manager = Employee::factory()->create(['user_id' => $this->managerUser->id]);
    
    $this->chantier = Chantier::factory()->create(['manager_id' => $this->managerUser->id]);
    
    $this->supplier = ThirdParty::factory()->create();
    
    $this->contract = RentalContract::factory()->create([
        'supplier_id' => $this->supplier->id,
        'chantier_id' => $this->chantier->id,
        'reference' => 'LOC-EXPIRE-001',
        'status' => RentalStatus::ACTIVE,
        'end_date' => Carbon::today()->addDays(3),
    ]);
});

afterEach(function () {
    Notification::fake();
});

it('envoie une notification au manager du chantier pour les contrats expirant dans 3 jours', function () {
    $job = new RentalExpirationAlertJob();
    $job->handle();
    
    Notification::assertSentTo(
        $this->managerUser,
        \App\Notifications\RentalExpirationAlert::class,
        function ($notification) {
            expect($notification->contract->id)->toBe($this->contract->id);
            expect($notification->daysUntilExpiration)->toBe(3);
            return true;
        }
    );
});

it('n\'envoie pas de notification pour les contrats non actifs', function () {
    $supplier2 = ThirdParty::factory()->create();
    
    RentalContract::factory()->create([
        'supplier_id' => $supplier2->id,
        'chantier_id' => $this->chantier->id,
        'reference' => 'LOC-DRAFT-EXPIRE',
        'status' => \App\Enums\Locations\RentalStatus::DRAFT,
        'end_date' => Carbon::today()->addDays(3),
    ]);
    
    $job = new RentalExpirationAlertJob();
    $job->handle();
    
    Notification::assertNotSentTo(
        $this->managerUser,
        \App\Notifications\RentalExpirationAlert::class,
        function ($notification) {
            return $notification->contract->reference === 'LOC-DRAFT-EXPIRE';
        }
    );
});

it('n\'envoie pas de notification si pas de manager sur le chantier', function () {
    $chantierSansManager = Chantier::factory()->create(['manager_id' => null]);
    $supplier3 = ThirdParty::factory()->create();
    
    RentalContract::factory()->create([
        'supplier_id' => $supplier3->id,
        'chantier_id' => $chantierSansManager->id,
        'reference' => 'LOC-NO-MANAGER',
        'status' => RentalStatus::ACTIVE,
        'end_date' => Carbon::today()->addDays(3),
    ]);
    
    $job = new RentalExpirationAlertJob();
    $job->handle();
    
    Notification::assertNotSentTo(
        $this->managerUser,
        \App\Notifications\RentalExpirationAlert::class,
        function ($notification) {
            return $notification->contract->reference === 'LOC-NO-MANAGER';
        }
    );
});

it('ne fait rien si aucun contrat n\'expire dans 3 jours', function () {
    Notification::fake();
    
    $supplier4 = ThirdParty::factory()->create();
    
    RentalContract::factory()->create([
        'supplier_id' => $supplier4->id,
        'chantier_id' => $this->chantier->id,
        'reference' => 'LOC-LATER-EXPIRE',
        'status' => RentalStatus::ACTIVE,
        'end_date' => Carbon::today()->addDays(10),
    ]);
    
    $job = new RentalExpirationAlertJob();
    $job->handle();
    
    Notification::assertNothingSent();
});