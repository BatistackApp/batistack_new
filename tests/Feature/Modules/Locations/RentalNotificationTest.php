<?php

use App\Models\Locations\RentalContract;
use App\Models\Chantiers\Chantier;
use App\Models\Tiers\ThirdParty;
use App\Notifications\RentalExpirationAlert;
use App\Notifications\RentalOverageAlert;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

beforeEach(function () {
    Notification::fake();
    
    $this->supplier = ThirdParty::factory()->create();
    $this->chantier = Chantier::factory()->create();
    
    $this->contract = RentalContract::factory()->create([
        'supplier_id' => $this->supplier->id,
        'chantier_id' => $this->chantier->id,
        'reference' => 'LOC-NOTIF-001',
        'end_date' => Carbon::today()->addDays(3),
        'expected_end_date' => Carbon::today(),
        'daily_penalty_rate' => 50,
        'penalty_amount' => 0,
    ]);
});

it('RentalExpirationAlert a le bon contenu mail', function () {
    $notification = new RentalExpirationAlert($this->contract, 3);
    
    $mail = $notification->toMail(new \App\Models\User(['name' => 'Test']));
    $this->assertStringContainsString($this->contract->reference, $mail->subject);
    $this->assertStringContainsString('3 jour(s)', $mail->render());
});

it('RentalExpirationAlert a le bon tableau pour database', function () {
    $notification = new RentalExpirationAlert($this->contract, 3);
    
    $array = $notification->toArray(new \App\Models\User());
    
    expect($array['type'])->toBe('rental_expiration');
    expect($array['contract_id'])->toBe($this->contract->id);
    expect($array['contract_reference'])->toBe($this->contract->reference);
    expect($array['days_until_expiration'])->toBe(3);
    expect($array['url'])->toContain('/locations/rental-contracts/');
});

it('RentalOverageAlert a le bon tableau pour database', function () {
    $notification = new RentalOverageAlert($this->contract, 5, 250.0, 500.0);
    
    $array = $notification->toArray(new \App\Models\User());
    
    expect($array['type'])->toBe('rental_overage');
    expect($array['contract_id'])->toBe($this->contract->id);
    expect($array['days_overdue'])->toBe(5);
    expect($array['penalty_amount'])->toBe(250.0);
    expect($array['total_penalty_amount'])->toBe(500.0);
});

it('RentalOverageAlert a le bon contenu mail', function () {
    $notification = new RentalOverageAlert($this->contract, 3, 150.0, 450.0);
    
    $mail = $notification->toMail(new \App\Models\User(['name' => 'Test Manager']));
    
    expect($mail->subject)->toContain('ALERTE DÉPASSEMENT LOCATION')
        ->and($mail->subject)->toContain($this->contract->reference);
    
    $rendered = (string) $mail->render();
    $this->assertStringContainsString('3 jour(s)', $rendered);
    $this->assertStringContainsString('150', $rendered);
    $this->assertStringContainsString('450', $rendered);
    $this->assertStringContainsString($this->contract->reference, $rendered);
});

it('RentalOverageAlert utilise les canaux database et mail', function () {
    $notification = new RentalOverageAlert($this->contract, 1, 50.0, 50.0);
    
    $channels = $notification->via(new \App\Models\User());
    
    expect($channels)->toBe(['database', 'mail']);
});

it('RentalExpirationAlert utilise les canaux database et mail', function () {
    $notification = new \App\Notifications\RentalExpirationAlert($this->contract, 3);
    
    $channels = $notification->via(new \App\Models\User());
    
    expect($channels)->toBe(['database', 'mail']);
});