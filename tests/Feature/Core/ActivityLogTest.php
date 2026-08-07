<?php

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerQuote;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

use App\Services\Commerce\CommerceDocumentService;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Act as user to log causer
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Mock document service to avoid PDF generation errors
    $mock = Mockery::mock(CommerceDocumentService::class);
    $mock->shouldReceive('generateCustomerInvoice')->andReturn('dummy_path');
    $mock->shouldReceive('generateCustomerQuote')->andReturn('dummy_path');
    $this->app->instance(CommerceDocumentService::class, $mock);
});

it('Chantier uses LogsActivity trait', function () {
    expect(class_uses_recursive(Chantier::class))->toContain(Spatie\Activitylog\Models\Concerns\LogsActivity::class);
});

it('CustomerInvoice uses LogsActivity trait', function () {
    expect(class_uses_recursive(CustomerInvoice::class))->toContain(Spatie\Activitylog\Models\Concerns\LogsActivity::class);
});

it('CustomerQuote uses LogsActivity trait', function () {
    expect(class_uses_recursive(CustomerQuote::class))->toContain(Spatie\Activitylog\Models\Concerns\LogsActivity::class);
});
