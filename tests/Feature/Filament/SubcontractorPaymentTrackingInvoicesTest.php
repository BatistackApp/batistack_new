<?php

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Filament\Subcontractor\Pages\PaymentTracking;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists only customer invoices addressed to the authenticated subcontractor', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $company = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    $otherCompany = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);

    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $company->id,
        'is_active' => true,
    ]);

    $invoice = CustomerInvoice::factory()->create(['client_id' => $company->id]);
    $otherInvoice = CustomerInvoice::factory()->create(['client_id' => $otherCompany->id]);

    $this->actingAs($user);
    $page = app(PaymentTracking::class);
    $visibleIds = $page->getCustomerInvoices()->modelKeys();

    expect($visibleIds)
        ->toContain($invoice->id)
        ->not->toContain($otherInvoice->id);
});

it('does not list customer invoices for a non-subcontractor account', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $client = ThirdParty::factory()->create(['type' => ThirdPartyType::CLIENT]);

    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $client->id,
        'is_active' => true,
    ]);

    CustomerInvoice::factory()->create(['client_id' => $client->id]);

    $this->actingAs($user);
    $page = app(PaymentTracking::class);

    expect($page->getCustomerInvoices())->toBeEmpty();
});

it('calculates payment tracking widgets from issued customer invoices', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $company = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);

    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $company->id,
        'is_active' => true,
    ]);

    CustomerInvoice::factory()->create([
        'client_id' => $company->id,
        'status' => InvoiceStatus::VALIDATED,
        'total_ttc' => 4500,
    ]);
    CustomerInvoice::factory()->create([
        'client_id' => $company->id,
        'status' => InvoiceStatus::DRAFT,
        'total_ttc' => 1200,
    ]);
    CustomerInvoice::factory()->create([
        'client_id' => $company->id,
        'status' => InvoiceStatus::CANCELED,
        'total_ttc' => 800,
    ]);

    $this->actingAs($user);
    $stats = app(PaymentTracking::class)->getCustomerInvoiceStats();

    expect($stats['total_facture'])->toBe(4500.0)
        ->and($stats['total_paye'])->toBe(0.0)
        ->and($stats['reste_a_encaisser'])->toBe(4500.0);
});


it('prevents a subcontractor from paying another company invoice', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $company = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);
    $otherCompany = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);

    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $company->id,
        'is_active' => true,
    ]);

    $invoice = CustomerInvoice::factory()->create([
        'client_id' => $otherCompany->id,
        'status' => InvoiceStatus::VALIDATED,
        'total_ttc' => 4500,
    ]);

    $url = URL::temporarySignedRoute('pay.subcontractor.invoice', now()->addMinutes(10), ['invoice' => $invoice->id]);

    $this->actingAs($user)->get($url)->assertForbidden();
});

it('rejects payment attempts for invoices that are not payable', function () {
    $user = User::factory()->create(['is_tiers' => true]);
    $company = ThirdParty::factory()->create(['type' => ThirdPartyType::SUBCONTRACTOR]);

    Contact::factory()->create([
        'user_id' => $user->id,
        'third_party_id' => $company->id,
        'is_active' => true,
    ]);

    $invoice = CustomerInvoice::factory()->create([
        'client_id' => $company->id,
        'status' => InvoiceStatus::PAID,
        'total_ttc' => 4500,
    ]);

    $url = URL::temporarySignedRoute('pay.subcontractor.invoice', now()->addMinutes(10), ['invoice' => $invoice->id]);

    $this->actingAs($user)->get($url)->assertForbidden();
});
