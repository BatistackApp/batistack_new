<?php

use App\Enums\Commerce\QuoteStatus;
use App\Jobs\Commerce\GenerateDocumentJob;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerQuote;
use App\Models\Core\Company;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Notifications\Commerce\QuoteAcceptedNotification;
use App\Notifications\Commerce\QuoteRejectedNotification;
use App\Notifications\Commerce\QuoteSentNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Company::factory()->create();
    $this->customer = ThirdParty::factory()->create();
    
    // Add primary contact for customer to receive notifications
    $this->contact = Contact::factory()->create([
        'third_party_id' => $this->customer->id,
        'is_primary' => true
    ]);
    
    $this->chantier = Chantier::factory()->create(['client_id' => $this->customer->id]);
    $this->user = User::factory()->create();
    
    Notification::fake();
    Queue::fake();
});

test('observer sets expires_at on creating', function () {
    $quote = CustomerQuote::factory()->create([
        'client_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'responsable_id' => $this->user->id,
        'expires_at' => null
    ]);
    
    expect($quote->expires_at)->not->toBeNull();
});

test('observer triggers handleQuoteSent on status SENT', function () {
    $this->actingAs($this->user);
    $quote = CustomerQuote::factory()->create([
        'client_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'responsable_id' => $this->user->id,
        'status' => QuoteStatus::DRAFT
    ]);
    
    $quote->update(['status' => QuoteStatus::SENT]);
    
    Queue::assertPushed(GenerateDocumentJob::class);
    Notification::assertSentTo(
        $this->contact,
        QuoteSentNotification::class
    );
});

test('observer triggers handleQuoteSigned on status SIGNED', function () {
    $quote = CustomerQuote::factory()->create([
        'client_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'responsable_id' => $this->user->id,
        'status' => QuoteStatus::SENT
    ]);
    
    $quote->update(['status' => QuoteStatus::SIGNED]);
    
    Notification::assertSentTo(
        $this->user,
        QuoteAcceptedNotification::class
    );
});

test('observer triggers handleQuoteRejected on status REJECTED', function () {
    $quote = CustomerQuote::factory()->create([
        'client_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'responsable_id' => $this->user->id,
        'status' => QuoteStatus::SENT
    ]);
    
    $quote->update(['status' => QuoteStatus::REJECTED]);
    
    Notification::assertSentTo(
        $this->user,
        QuoteRejectedNotification::class
    );
});
