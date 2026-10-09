<?php

namespace Tests\Feature\Customer;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\QuoteStatus;
use App\Filament\Customer\Resources\CustomerDeliveryNotes\CustomerDeliveryNoteResource;
use App\Filament\Customer\Resources\CustomerInvoices\CustomerInvoiceResource;
use App\Filament\Customer\Resources\CustomerOrders\CustomerOrderResource;
use App\Filament\Customer\Resources\CustomerQuotes\CustomerQuoteResource;
use App\Filament\Customer\Resources\CustomerSituations\CustomerSituationResource;
use App\Models\Commerce\CustomerDeliveryNote;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerSituation;
use App\Models\Commerce\CustomerQuote;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

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

    expect(CustomerInvoiceResource::canView($ownInvoice))->toBeTrue()
        ->and(CustomerInvoiceResource::canView($otherInvoice))->toBeFalse()
        ->and(CustomerInvoiceResource::canEdit($ownInvoice))->toBeFalse()
        ->and(CustomerInvoiceResource::canDelete($ownInvoice))->toBeFalse();
});

it('rejects unsigned and tampered invoice payment URLs', function () {
    // Use an existing invoice so implicit route-model binding does not return
    // 404 before the signed middleware can validate the URL.
    $invoice = CustomerInvoice::withoutEvents(fn () => CustomerInvoice::factory()->create([
        'status' => InvoiceStatus::VALIDATED,
    ]));

    $unsignedUrl = route('pay.invoice', ['invoice' => $invoice->getRouteKey()], absolute: false);

    $this->get($unsignedUrl)->assertForbidden();

    $signedUrl = URL::signedRoute('pay.invoice', [
        'invoice' => $invoice->getRouteKey(),
    ]);

    expect(URL::hasValidSignature(Request::create($signedUrl)))->toBeTrue();

    $tamperedUrl = $signedUrl.'&invoice=1';

    expect(URL::hasValidSignature(Request::create($tamperedUrl)))->toBeFalse();

    $this->get($tamperedUrl)->assertForbidden();
});

it('rejects direct access to another customers order page', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $ownThirdParty = ThirdParty::factory()->create(['type' => 'client']);
    $otherThirdParty = ThirdParty::factory()->create(['type' => 'client']);
    authenticateCustomerForThirdParty($user, $ownThirdParty);

    $ownOrder = CustomerOrder::withoutEvents(fn () => CustomerOrder::factory()->create(['client_id' => $ownThirdParty->id]));
    $otherOrder = CustomerOrder::withoutEvents(fn () => CustomerOrder::factory()->create(['client_id' => $otherThirdParty->id]));

    $this->actingAs($user);
    $this->get(CustomerOrderResource::getUrl('view', ['record' => $ownOrder], panel: 'customer'))->assertSuccessful();
    $this->get(CustomerOrderResource::getUrl('view', ['record' => $otherOrder], panel: 'customer'))->assertNotFound();
});

it('rejects direct access to another customers delivery note page', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $ownThirdParty = ThirdParty::factory()->create(['type' => 'client']);
    $otherThirdParty = ThirdParty::factory()->create(['type' => 'client']);
    authenticateCustomerForThirdParty($user, $ownThirdParty);

    $ownNote = CustomerDeliveryNote::withoutEvents(fn () => CustomerDeliveryNote::factory()->create(['client_id' => $ownThirdParty->id]));
    $otherNote = CustomerDeliveryNote::withoutEvents(fn () => CustomerDeliveryNote::factory()->create(['client_id' => $otherThirdParty->id]));

    $this->actingAs($user);
    $this->get(CustomerDeliveryNoteResource::getUrl('view', ['record' => $ownNote], panel: 'customer'))->assertSuccessful();
    $this->get(CustomerDeliveryNoteResource::getUrl('view', ['record' => $otherNote], panel: 'customer'))->assertNotFound();
});

it('rejects direct access to another customers situation page', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $ownThirdParty = ThirdParty::factory()->create(['type' => 'client']);
    $otherThirdParty = ThirdParty::factory()->create(['type' => 'client']);
    authenticateCustomerForThirdParty($user, $ownThirdParty);

    $ownOrder = CustomerOrder::withoutEvents(fn () => CustomerOrder::factory()->create(['client_id' => $ownThirdParty->id]));
    $otherOrder = CustomerOrder::withoutEvents(fn () => CustomerOrder::factory()->create(['client_id' => $otherThirdParty->id]));
    $ownSituation = CustomerSituation::withoutEvents(fn () => CustomerSituation::factory()->create(['customer_order_id' => $ownOrder->id]));
    $otherSituation = CustomerSituation::withoutEvents(fn () => CustomerSituation::factory()->create(['customer_order_id' => $otherOrder->id]));

    $this->actingAs($user);
    $this->get(CustomerSituationResource::getUrl('view', ['record' => $ownSituation], panel: 'customer'))->assertSuccessful();
    $this->get(CustomerSituationResource::getUrl('view', ['record' => $otherSituation], panel: 'customer'))->assertNotFound();
});

