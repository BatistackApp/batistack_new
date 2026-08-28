<?php

use App\Enums\Commerce\QuoteStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerQuote;
use App\Models\Commerce\CustomerQuoteItem;
use App\Models\Core\Company;
use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use App\Models\User;

beforeEach(function () {
    Company::factory()->create();
    $this->customer = ThirdParty::factory()->create();
    $this->chantier = Chantier::factory()->create(['client_id' => $this->customer->id]);
    $this->user = User::factory()->create();
    $this->quote = CustomerQuote::factory()->create([
        'client_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'responsable_id' => $this->user->id,
    ]);
});

test('quote has correct relationships', function () {
    expect($this->quote->client)->toBeInstanceOf(ThirdParty::class)
        ->and($this->quote->chantier)->toBeInstanceOf(Chantier::class)
        ->and($this->quote->user)->toBeInstanceOf(User::class);
});

test('quote calculates total tva correctly', function () {
    $vatRate = VatRate::factory()->create(['rate' => 20]);

    // total_ht = 100 * 2 = 200 => TVA = 40
    CustomerQuoteItem::factory()->create([
        'customer_quote_id' => $this->quote->id,
        'quantity' => 2,
        'selling_price' => 100,
        'total_ht' => 200,
        'vat_rate_id' => $vatRate->id,
    ]);

    // total_ht = 50 * 3 = 150 => TVA = 30
    CustomerQuoteItem::factory()->create([
        'customer_quote_id' => $this->quote->id,
        'quantity' => 3,
        'selling_price' => 50,
        'total_ht' => 150,
        'vat_rate_id' => $vatRate->id,
    ]);

    $this->quote->refresh();

    expect($this->quote->total_tva)->toBeFloat()->toEqual(70.0);
});

test('quote calculates is expired correctly', function () {
    $this->quote->update(['expires_at' => now()->subDay()]);
    expect($this->quote->is_expired)->toBeTrue();

    $this->quote->update(['expires_at' => now()->addDay()]);
    expect($this->quote->is_expired)->toBeFalse();
});

test('quote casts attributes correctly', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->quote->update(['status' => QuoteStatus::SENT]);

    expect($this->quote->status)->toBeInstanceOf(QuoteStatus::class)
        ->and($this->quote->status)->toBe(QuoteStatus::SENT);
});
