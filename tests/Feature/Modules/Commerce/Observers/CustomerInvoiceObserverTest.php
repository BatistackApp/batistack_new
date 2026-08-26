<?php

use App\Enums\Commerce\InvoiceStatus;
use App\Jobs\Commerce\GenerateDocumentJob;
use App\Jobs\Commerce\SendCustomerInvoiceEmailJob;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Core\Company;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Notifications\Commerce\InvoiceGeneratedNotification;
use App\Notifications\Commerce\InvoicePaidNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Company::factory()->create();
    $this->customer = ThirdParty::factory()->create();

    // Add primary contact for customer to receive notifications
    $this->contact = Contact::factory()->create([
        'third_party_id' => $this->customer->id,
        'is_primary' => true,
    ]);

    $this->chantier = Chantier::factory()->create(['client_id' => $this->customer->id]);
    $this->user = User::factory()->create();

    Notification::fake();
    Queue::fake();
});

test('observer sets due_date on creating', function () {
    $invoice = CustomerInvoice::factory()->create([
        'client_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'responsable_id' => $this->user->id,
        'due_date' => null,
    ]);

    expect($invoice->due_date)->not->toBeNull();
});

test('observer generates document on created', function () {
    CustomerInvoice::factory()->create([
        'client_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'responsable_id' => $this->user->id,
    ]);

    Queue::assertPushed(GenerateDocumentJob::class);
});

test('observer triggers validation logic on status VALIDATED', function () {
    $invoice = CustomerInvoice::factory()->create([
        'client_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'responsable_id' => $this->user->id,
        'status' => InvoiceStatus::DRAFT,
    ]);

    $invoice->update(['status' => InvoiceStatus::VALIDATED]);

    Queue::assertPushed(GenerateDocumentJob::class); // It generates document again
    Queue::assertPushed(SendCustomerInvoiceEmailJob::class);

    Notification::assertSentTo(
        $this->contact,
        InvoiceGeneratedNotification::class
    );
});

test('observer triggers paid logic on status PAID', function () {
    $invoice = CustomerInvoice::factory()->create([
        'client_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'responsable_id' => $this->user->id,
        'status' => InvoiceStatus::VALIDATED,
    ]);

    $invoice->update(['status' => InvoiceStatus::PAID]);

    Notification::assertSentTo(
        $this->contact,
        InvoicePaidNotification::class
    );
});
