<?php

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerQuote;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Services\Commerce\CommerceDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Act as user to log causer
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Create a company to avoid PDF generation errors in views
    Company::factory()->create(['legal_name' => 'Test Company']);

    // Mock document service to avoid PDF generation errors
    $mock = Mockery::mock(CommerceDocumentService::class);
    $mock->shouldReceive('generateCustomerInvoice')->andReturn('dummy_path');
    $mock->shouldReceive('generateCustomerQuote')->andReturn('dummy_path');
    $this->app->instance(CommerceDocumentService::class, $mock);
});

it('logs activity when a Chantier is created and updated', function () {
    $chantier = Chantier::factory()->create(['name' => 'Original Name']);

    $creationLog = Activity::where('subject_type', Chantier::class)
        ->where('subject_id', $chantier->id)
        ->where('event', 'created')
        ->first();

    expect($creationLog)->not->toBeNull()
        ->and($creationLog->causer_id)->toBe($this->user->id);

    $chantier->update(['name' => 'Updated Name']);

    $updateLog = Activity::where('subject_type', Chantier::class)
        ->where('subject_id', $chantier->id)
        ->where('event', 'updated')
        ->first();

    expect($updateLog)->not->toBeNull();
});

it('logs activity when a CustomerInvoice is created and updated', function () {
    $client = ThirdParty::factory()->create();
    $chantier = Chantier::factory()->create();
    $invoice = CustomerInvoice::factory()->create([
        'client_id' => $client->id,
        'chantier_id' => $chantier->id,
        'total_ht' => 100,
        'reference' => 'INV-001',
    ]);

    $creationLog = Activity::where('subject_type', CustomerInvoice::class)
        ->where('subject_id', $invoice->id)
        ->where('event', 'created')
        ->first();

    expect($creationLog)->not->toBeNull();

    $invoice->update(['total_ht' => 200]);

    $updateLog = Activity::where('subject_type', CustomerInvoice::class)
        ->where('subject_id', $invoice->id)
        ->where('event', 'updated')
        ->first();

    expect($updateLog)->not->toBeNull();
});

it('logs activity when a CustomerQuote is created and updated', function () {
    $client = ThirdParty::factory()->create();
    $chantier = Chantier::factory()->create();
    $quote = CustomerQuote::factory()->create([
        'client_id' => $client->id,
        'chantier_id' => $chantier->id,
        'total_ht' => 500,
    ]);

    $creationLog = Activity::where('subject_type', CustomerQuote::class)
        ->where('subject_id', $quote->id)
        ->where('event', 'created')
        ->first();

    expect($creationLog)->not->toBeNull();

    $quote->update(['total_ht' => 1000]);

    $updateLog = Activity::where('subject_type', CustomerQuote::class)
        ->where('subject_id', $quote->id)
        ->where('event', 'updated')
        ->first();

    expect($updateLog)->not->toBeNull();
});
