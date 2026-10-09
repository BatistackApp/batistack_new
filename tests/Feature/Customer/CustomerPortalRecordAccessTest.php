<?php

namespace Tests\Feature\Customer;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\QuoteStatus;
use App\Filament\Customer\Resources\ClientEquipment\ClientEquipmentResource;
use App\Filament\Customer\Resources\CustomerDeliveryNotes\CustomerDeliveryNoteResource;
use App\Filament\Customer\Resources\CustomerDeliveryNotes\Pages\ListCustomerDeliveryNotes;
use App\Filament\Customer\Resources\CustomerInvoices\CustomerInvoiceResource;
use App\Filament\Customer\Resources\CustomerInvoices\Pages\ListCustomerInvoices;
use App\Filament\Customer\Resources\CustomerInvoices\Pages\ViewCustomerInvoice;
use App\Filament\Customer\Resources\Interventions\InterventionResource;
use App\Filament\Customer\Resources\CustomerOrders\CustomerOrderResource;
use App\Filament\Customer\Resources\CustomerOrders\Pages\ListCustomerOrders;
use App\Filament\Customer\Resources\CustomerQuotes\CustomerQuoteResource;
use App\Filament\Customer\Resources\CustomerQuotes\Pages\ListCustomerQuotes;
use App\Filament\Customer\Resources\CustomerSituations\CustomerSituationResource;
use App\Filament\Customer\Resources\CustomerSituations\Pages\ListCustomerSituations;
use App\Models\Commerce\CustomerDeliveryNote;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerQuote;
use App\Models\Commerce\CustomerSituation;
use App\Models\Core\Company;
use App\Models\Interventions\ClientEquipment;
use App\Models\Interventions\Intervention;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Services\Commerce\CommerceDocumentationService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Mockery;

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

it('executes the invoice PDF download action only for an authorized invoice', function () {
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

    $documentationService = Mockery::mock(CommerceDocumentationService::class);
    $documentationService
        ->shouldReceive('generateInvoicePdf')
        ->once()
        ->with(Mockery::on(fn (CustomerInvoice $invoice) => $invoice->is($ownInvoice)))
        ->andThrow(new \RuntimeException('Stop after verifying the authorized invoice.'));
    $this->app->instance(CommerceDocumentationService::class, $documentationService);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('customer'));

    Livewire::test(ViewCustomerInvoice::class, ['record' => $ownInvoice->getRouteKey()])
        ->callAction('downloadPdf');

    $this->get(CustomerInvoiceResource::getUrl('view', ['record' => $otherInvoice], panel: 'customer'))
        ->assertNotFound();
});

it('rejects a foreign invoice before the PDF action can be mounted', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $ownThirdParty = ThirdParty::factory()->create(['type' => 'client']);
    $otherThirdParty = ThirdParty::factory()->create(['type' => 'client']);

    authenticateCustomerForThirdParty($user, $ownThirdParty);

    $otherInvoice = CustomerInvoice::withoutEvents(fn () => CustomerInvoice::factory()->create([
        'client_id' => $otherThirdParty->id,
        'status' => InvoiceStatus::VALIDATED,
    ]));

    $documentationService = Mockery::mock(CommerceDocumentationService::class);
    $documentationService->shouldNotReceive('generateInvoicePdf');
    $this->app->instance(CommerceDocumentationService::class, $documentationService);

    $this->actingAs($user);

    // The record must be rejected before Filament can mount the page and expose
    // its download action. The documentation service must never be invoked.
    $this->get(CustomerInvoiceResource::getUrl('view', ['record' => $otherInvoice], panel: 'customer'))
        ->assertNotFound();
});

it('allows customers to view their own equipment and rejects another customers equipment', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $ownThirdParty = ThirdParty::factory()->create(['type' => 'client']);
    $otherThirdParty = ThirdParty::factory()->create(['type' => 'client']);

    authenticateCustomerForThirdParty($user, $ownThirdParty);

    $ownEquipment = ClientEquipment::withoutEvents(fn () => ClientEquipment::factory()->create([
        'third_party_id' => $ownThirdParty->id,
    ]));
    $otherEquipment = ClientEquipment::withoutEvents(fn () => ClientEquipment::factory()->create([
        'third_party_id' => $otherThirdParty->id,
    ]));

    $this->actingAs($user);

    $this->get(ClientEquipmentResource::getUrl('view', ['record' => $ownEquipment], panel: 'customer'))
        ->assertSuccessful();

    $this->get(ClientEquipmentResource::getUrl('view', ['record' => $otherEquipment], panel: 'customer'))
        ->assertNotFound();
});

it('allows customers to view their own interventions and rejects another customers interventions', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $ownThirdParty = ThirdParty::factory()->create(['type' => 'client']);
    $otherThirdParty = ThirdParty::factory()->create(['type' => 'client']);

    authenticateCustomerForThirdParty($user, $ownThirdParty);

    $company = Company::factory()->create();

    $ownIntervention = Intervention::withoutEvents(fn () => Intervention::factory()->create([
        'company_id' => $company->id,
        'reference' => 'INT-'.now()->format('Y').'-9001',
        'third_party_id' => $ownThirdParty->id,
    ]));
    $otherIntervention = Intervention::withoutEvents(fn () => Intervention::factory()->create([
        'company_id' => $company->id,
        'reference' => 'INT-'.now()->format('Y').'-9002',
        'third_party_id' => $otherThirdParty->id,
    ]));

    $this->actingAs($user);

    $this->get(InterventionResource::getUrl('view', ['record' => $ownIntervention], panel: 'customer'))
        ->assertSuccessful();

    $this->get(InterventionResource::getUrl('view', ['record' => $otherIntervention], panel: 'customer'))
        ->assertNotFound();
});

it('renders a valid signed payment URL for an authorized invoice', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $thirdParty = ThirdParty::factory()->create(['type' => 'client']);

    authenticateCustomerForThirdParty($user, $thirdParty);

    $invoice = CustomerInvoice::withoutEvents(fn () => CustomerInvoice::factory()->create([
        'client_id' => $thirdParty->id,
        'status' => InvoiceStatus::VALIDATED,
    ]));

    $this->travelTo(now());
    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('customer'));

    $expectedSignedUrl = URL::temporarySignedRoute('pay.invoice', now()->addMinutes(30), [
        'invoice' => $invoice->id,
    ]);

    Livewire::test(ViewCustomerInvoice::class, ['record' => $invoice->getRouteKey()])
        ->assertActionHasUrl('payOnline', $expectedSignedUrl);

    expect(URL::hasValidSignature(Request::create($expectedSignedUrl)))->toBeTrue();
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

    $expiredUrl = URL::temporarySignedRoute('pay.invoice', now()->subMinute(), [
        'invoice' => $invoice->getRouteKey(),
    ]);

    $this->get($expiredUrl)->assertForbidden();
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

it('scopes the customer quote list to the authenticated customer', function () {
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
    Filament::setCurrentPanel(Filament::getPanel('customer'));

    Livewire::test(ListCustomerQuotes::class)
        ->assertCanSeeTableRecords([$ownQuote])
        ->assertCanNotSeeTableRecords([$otherQuote]);
});

it('scopes the customer invoice list to the authenticated customer', function () {
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
    Filament::setCurrentPanel(Filament::getPanel('customer'));

    Livewire::test(ListCustomerInvoices::class)
        ->assertCanSeeTableRecords([$ownInvoice])
        ->assertCanNotSeeTableRecords([$otherInvoice]);
});

it('scopes the customer order list to the authenticated customer', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $ownThirdParty = ThirdParty::factory()->create(['type' => 'client']);
    $otherThirdParty = ThirdParty::factory()->create(['type' => 'client']);
    authenticateCustomerForThirdParty($user, $ownThirdParty);

    $ownOrder = CustomerOrder::withoutEvents(fn () => CustomerOrder::factory()->create(['client_id' => $ownThirdParty->id]));
    $otherOrder = CustomerOrder::withoutEvents(fn () => CustomerOrder::factory()->create(['client_id' => $otherThirdParty->id]));

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('customer'));

    Livewire::test(ListCustomerOrders::class)
        ->assertCanSeeTableRecords([$ownOrder])
        ->assertCanNotSeeTableRecords([$otherOrder]);
});

it('scopes the customer situation list through the owning order', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $ownThirdParty = ThirdParty::factory()->create(['type' => 'client']);
    $otherThirdParty = ThirdParty::factory()->create(['type' => 'client']);
    authenticateCustomerForThirdParty($user, $ownThirdParty);

    $ownOrder = CustomerOrder::withoutEvents(fn () => CustomerOrder::factory()->create(['client_id' => $ownThirdParty->id]));
    $otherOrder = CustomerOrder::withoutEvents(fn () => CustomerOrder::factory()->create(['client_id' => $otherThirdParty->id]));
    $ownSituation = CustomerSituation::withoutEvents(fn () => CustomerSituation::factory()->create(['customer_order_id' => $ownOrder->id]));
    $otherSituation = CustomerSituation::withoutEvents(fn () => CustomerSituation::factory()->create(['customer_order_id' => $otherOrder->id]));

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('customer'));

    Livewire::test(ListCustomerSituations::class)
        ->assertCanSeeTableRecords([$ownSituation])
        ->assertCanNotSeeTableRecords([$otherSituation]);
});

it('scopes the customer delivery note list to the authenticated customer', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $ownThirdParty = ThirdParty::factory()->create(['type' => 'client']);
    $otherThirdParty = ThirdParty::factory()->create(['type' => 'client']);
    authenticateCustomerForThirdParty($user, $ownThirdParty);

    $ownNote = CustomerDeliveryNote::withoutEvents(fn () => CustomerDeliveryNote::factory()->create(['client_id' => $ownThirdParty->id]));
    $otherNote = CustomerDeliveryNote::withoutEvents(fn () => CustomerDeliveryNote::factory()->create(['client_id' => $otherThirdParty->id]));

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('customer'));

    Livewire::test(ListCustomerDeliveryNotes::class)
        ->assertCanSeeTableRecords([$ownNote])
        ->assertCanNotSeeTableRecords([$otherNote]);
});
