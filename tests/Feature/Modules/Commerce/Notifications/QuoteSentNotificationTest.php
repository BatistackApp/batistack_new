<?php

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerQuote;
use App\Models\Core\Company;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Notifications\Commerce\QuoteSentNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Company::factory()->create();
    $this->customer = ThirdParty::factory()->create();
    $this->contact = Contact::factory()->create([
        'third_party_id' => $this->customer->id,
        'is_primary' => true
    ]);
    
    $this->chantier = Chantier::factory()->create(['client_id' => $this->customer->id]);
    $this->user = User::factory()->create();
    
    $this->quote = CustomerQuote::factory()->create([
        'client_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'responsable_id' => $this->user->id,
        'reference' => 'DEV-TEST-01'
    ]);
});

test('notification content is correct', function () {
    $notification = new QuoteSentNotification($this->quote);
    
    // Test Mail Representation
    $mailData = $notification->toMail($this->contact);
    
    expect($mailData->subject)->toContain('Devis n°DEV-TEST-01')
        ->and($mailData->introLines[0])->toContain('Veuillez trouver ci-joint votre devis')
        ->and($mailData->actionText)->toBe('Consulter le devis')
        ->and($mailData->actionUrl)->not->toBeNull();
});
