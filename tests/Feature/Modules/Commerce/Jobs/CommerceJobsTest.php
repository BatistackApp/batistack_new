<?php

namespace Tests\Feature\Modules\Commerce\Jobs;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\QuoteStatus;
use App\Jobs\Commerce\CheckExpiredQuotesJob;
use App\Jobs\Commerce\CheckOverdueInvoicesJob;
use App\Jobs\Commerce\GenerateDocumentJob;
use App\Jobs\Commerce\SendCustomerInvoiceEmailJob;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Commerce\CustomerQuote;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Notifications\Commerce\InvoiceGeneratedNotification;
use App\Notifications\Commerce\PaymentReminderNotification;
use App\Notifications\Commerce\QuoteExpiredNotification;
use App\Services\Commerce\CommerceDocumentationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use App\Mail\Commerce\CustomerInvoiceMail;
use Illuminate\Support\Facades\Queue;
use Mockery;

uses(RefreshDatabase::class);

describe('Commerce Jobs', function () {
    beforeEach(function () {
        Queue::fake([GenerateDocumentJob::class]);
    });

    it('checks expired quotes and notifies user', function () {
        Notification::fake();
        $user = User::factory()->create();
        
        $quote = CustomerQuote::factory()->create([
            'status' => QuoteStatus::SENT,
            'responsable_id' => $user->id,
            'expires_at' => now()->subDay(),
        ]);

        (new CheckExpiredQuotesJob())->handle();

        expect($quote->fresh()->status)->toBe(QuoteStatus::EXPIRED);
        Notification::assertSentTo($user, QuoteExpiredNotification::class);
    });

    it('checks overdue invoices and sends reminder', function () {
        Notification::fake();
        
        \App\Models\Core\Setting::factory()->create();
        
        $invoice = CustomerInvoice::factory()->create([
            'status' => InvoiceStatus::VALIDATED,
            'due_date' => now()->subDays(5),
            'total_ht' => 1000,
            'total_ttc' => 1200,
        ]);
        
        $client = $invoice->client;
        $contact = Contact::factory()->create(['third_party_id' => $client->id, 'is_primary' => true]);

        CheckOverdueInvoicesJob::dispatch();
        
        Notification::assertSentTo(
            $contact,
            PaymentReminderNotification::class
        );
    });

    it('generates quote document via job', function () {
        $quote = CustomerQuote::factory()->create();
        $job = new GenerateDocumentJob('quote', $quote);

        $serviceMock = Mockery::mock(CommerceDocumentationService::class);
        $serviceMock->shouldReceive('generateQuotePdf')->once();
            
        app()->instance(CommerceDocumentationService::class, $serviceMock);

        $job->handle();

        expect(true)->toBeTrue();
    });

    it('generates invoice document via job', function () {
        $invoice = CustomerInvoice::factory()->create();
        $job = new GenerateDocumentJob('invoice', $invoice);

        $serviceMock = Mockery::mock(CommerceDocumentationService::class);
        $serviceMock->shouldReceive('generateInvoicePdf')->once();
            
        app()->instance(CommerceDocumentationService::class, $serviceMock);

        $job->handle();

        expect(true)->toBeTrue();
    });

    it('sends customer invoice email', function () {
        Notification::fake();
        
        $invoice = CustomerInvoice::factory()->create([
            'status' => InvoiceStatus::VALIDATED
        ]);
        $client = $invoice->client;
        $contact = Contact::factory()->create(['third_party_id' => $client->id, 'is_primary' => true]);

        SendCustomerInvoiceEmailJob::dispatch($invoice);

        Notification::assertSentTo(
            $contact,
            \App\Notifications\Commerce\InvoiceGeneratedNotification::class
        );
    });
});
