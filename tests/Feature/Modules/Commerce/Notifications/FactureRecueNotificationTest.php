<?php

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Core\Company;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Notifications\Customer\FactureRecueNotification;

beforeEach(function () {
    Company::factory()->create();
    $this->customer = ThirdParty::factory()->create();
    $this->contact = Contact::factory()->create([
        'third_party_id' => $this->customer->id,
        'is_primary' => true,
    ]);

    $this->chantier = Chantier::factory()->create(['client_id' => $this->customer->id]);
    $this->user = User::factory()->create();

    $this->invoice = CustomerInvoice::factory()->create([
        'client_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'responsable_id' => $this->user->id,
        'reference' => 'FAC-2026-001',
        'total_ttc' => 1500.50,
        'due_date' => now()->addDays(30),
    ]);
});

test('notification uses mail and database channels', function () {
    $notification = new FactureRecueNotification($this->invoice);

    expect($notification->via($this->contact))->toBe(['mail', 'database']);
});

test('mail notification has correct subject', function () {
    $notification = new FactureRecueNotification($this->invoice);

    $mail = $notification->toMail($this->contact);

    expect($mail->subject)->toBe('Nouvelle facture n°FAC-2026-001');
});

test('mail notification has correct greeting with type label', function () {
    $notification = new FactureRecueNotification($this->invoice);

    $mail = $notification->toMail($this->contact);

    expect($mail->greeting)->toContain('Facture');
    expect($mail->greeting)->toContain('reçue');
});

test('mail notification contains invoice reference in lines', function () {
    $notification = new FactureRecueNotification($this->invoice);

    $mail = $notification->toMail($this->contact);

    $hasReference = collect($mail->introLines)->contains(fn ($line) => str_contains($line, 'FAC-2026-001'));
    expect($hasReference)->toBeTrue();
});

test('mail notification contains formatted total ttc', function () {
    $notification = new FactureRecueNotification($this->invoice);

    $mail = $notification->toMail($this->contact);

    $hasAmount = collect($mail->introLines)->contains(fn ($line) => str_contains($line, '1 500,50'));
    expect($hasAmount)->toBeTrue();
});

test('mail notification has correct action url', function () {
    $notification = new FactureRecueNotification($this->invoice);

    $mail = $notification->toMail($this->contact);

    expect($mail->actionUrl)->toContain("/customer/customer-invoices/{$this->invoice->id}");
    expect($mail->actionText)->toBe('Consulter la facture');
});

test('mail notification contains due date', function () {
    $notification = new FactureRecueNotification($this->invoice);

    $mail = $notification->toMail($this->contact);

    $hasDueDate = collect($mail->introLines)->contains(
        fn ($line) => str_contains($line, $this->invoice->due_date->format('d/m/Y'))
    );
    expect($hasDueDate)->toBeTrue();
});

test('database notification has correct title', function () {
    $notification = new FactureRecueNotification($this->invoice);

    $databaseData = $notification->toDatabase($this->contact);

    expect($databaseData['title'])->toContain('FAC-2026-001');
    expect($databaseData['title'])->toContain('reçue');
});

test('database notification body contains amount', function () {
    $notification = new FactureRecueNotification($this->invoice);

    $databaseData = $notification->toDatabase($this->contact);

    expect($databaseData['body'])->toContain('1 500,50');
});

test('toArray returns empty array', function () {
    $notification = new FactureRecueNotification($this->invoice);

    expect($notification->toArray($this->contact))->toBe([]);
});
