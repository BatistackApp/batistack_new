<?php

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Core\Company;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Notifications\Customer\PaiementRecuNotification;

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
        'reference' => 'FAC-2026-042',
        'total_ttc' => 3200.00,
    ]);
});

test('notification uses mail and database channels', function () {
    $notification = new PaiementRecuNotification($this->invoice);

    expect($notification->via($this->contact))->toBe(['mail', 'database']);
});

test('mail notification has correct subject', function () {
    $notification = new PaiementRecuNotification($this->invoice);

    $mail = $notification->toMail($this->contact);

    expect($mail->subject)->toBe('Paiement reçu — Facture FAC-2026-042');
});

test('mail notification has correct greeting', function () {
    $notification = new PaiementRecuNotification($this->invoice);

    $mail = $notification->toMail($this->contact);

    expect($mail->greeting)->toBe('Paiement enregistré');
});

test('mail notification contains reference in lines', function () {
    $notification = new PaiementRecuNotification($this->invoice);

    $mail = $notification->toMail($this->contact);

    $hasRef = collect($mail->introLines)->contains(fn ($line) => str_contains($line, 'FAC-2026-042'));
    expect($hasRef)->toBeTrue();
});

test('mail notification contains formatted amount', function () {
    $notification = new PaiementRecuNotification($this->invoice);

    $mail = $notification->toMail($this->contact);

    $hasAmount = collect($mail->introLines)->contains(fn ($line) => str_contains($line, '3 200,00'));
    expect($hasAmount)->toBeTrue();
});

test('mail notification has correct action url', function () {
    $notification = new PaiementRecuNotification($this->invoice);

    $mail = $notification->toMail($this->contact);

    expect($mail->actionUrl)->toContain("/customer/customer-invoices/{$this->invoice->id}");
    expect($mail->actionText)->toBe('Voir la facture');
});

test('mail notification contains today date', function () {
    $notification = new PaiementRecuNotification($this->invoice);

    $mail = $notification->toMail($this->contact);

    $hasDate = collect($mail->introLines)->contains(fn ($line) => str_contains($line, now()->format('d/m/Y')));
    expect($hasDate)->toBeTrue();
});

test('database notification has correct title', function () {
    $notification = new PaiementRecuNotification($this->invoice);

    $databaseData = $notification->toDatabase($this->contact);

    expect($databaseData['title'])->toContain('FAC-2026-042');
    expect($databaseData['title'])->toContain('Paiement reçu');
});

test('database notification body contains amount', function () {
    $notification = new PaiementRecuNotification($this->invoice);

    $databaseData = $notification->toDatabase($this->contact);

    expect($databaseData['body'])->toContain('3 200,00');
});

test('toArray returns empty array', function () {
    $notification = new PaiementRecuNotification($this->invoice);

    expect($notification->toArray($this->contact))->toBe([]);
});
