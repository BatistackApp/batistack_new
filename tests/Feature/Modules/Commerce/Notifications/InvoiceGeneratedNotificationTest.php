<?php

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Core\Company;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Notifications\Commerce\InvoiceGeneratedNotification;
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
    
    $this->invoice = CustomerInvoice::factory()->create([
        'client_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'responsable_id' => $this->user->id,
        'reference' => 'FAC-TEST-01'
    ]);
});

test('notification content is correct', function () {
    $notification = new InvoiceGeneratedNotification($this->invoice);
    
    $mailData = $notification->toMail($this->contact);
    
    expect($mailData->subject)->toContain('Facture n°FAC-TEST-01')
        ->and($mailData->introLines[0])->toContain('Nous vous adressons la facture relative à vos derniers achats')
        ->and($mailData->actionText)->toBe('Télécharger la facture')
        ->and($mailData->actionUrl)->not->toBeNull();
});
