<?php

namespace Tests\Feature\Customer;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\QuoteStatus;
use App\Filament\Customer\Resources\CustomerInvoices\CustomerInvoiceResource;
use App\Filament\Customer\Resources\CustomerQuotes\CustomerQuoteResource;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerQuote;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function authenticateCustomerForThirdParty(User $user, ThirdParty $thirdParty): void
{
    Contact::withoutEvents(fn () => Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $thirdParty->id,
        'is_active' => true,
    ]));
}

it('rejects direct access to another customers quote page', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $ownThirdParty = ThirdParty::factory()->create(['type' => 'client']);
    $otherThirdParty = ThirdParty::factory()->create(['type' => 'client']);

    authenticateCustomerForThirdParty($user, $ownThirdParty);

    $ownQuote = CustomerQuote::factory()->create([
        'client_id' => $ownThirdParty->id,
        'status' => QuoteStatus::SENT,
    ]);
    $otherQuote = CustomerQuote::factory()->create([
        'client_id' => $otherThirdParty->id,
        'status' => QuoteStatus::SENT,
    ]);

    $this->actingAs($user);

    $this->get(CustomerQuoteResource::getUrl('view', ['record' => $ownQuote], panel: 'customer'))
        ->assertSuccessful();

    $this->get(CustomerQuoteResource::getUrl('view', ['record' => $otherQuote], panel: 'customer'))
        ->assertNotFound();
});

it('rejects direct access to another customers invoice page and its download action entry point', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $ownThirdParty = ThirdParty::factory()->create(['type' => 'client']);
    $otherThirdParty = ThirdParty::factory()->create(['type' => 'client']);

    authenticateCustomerForThirdParty($user, $ownThirdParty);

    $ownInvoice = CustomerInvoice::withoutEvents(fn () => CustomerInvoice::factory()->create([
        'client_id' => $ownThirdParty->id,
        'status' => InvoiceStatus::VALIDATED,
    ]));
    $otherInvoice = CustomerInvoice::withoutEvents(fn () => CustomerInvoice::factory()->create([
        'client_id' => $otherThirdParty->id,
        'status' => InvoiceStatus::VALIDATED,
    ]));

    $this->actingAs($user);

    $this->get(CustomerInvoiceResource::getUrl('view', ['record' => $ownInvoice], panel: 'customer'))
        ->assertSuccessful();

    $this->get(CustomerInvoiceResource::getUrl('view', ['record' => $otherInvoice], panel: 'customer'))
        ->assertNotFound();
});
