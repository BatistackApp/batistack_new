<?php

use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Core\Company;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Notifications\Customer\RelanceNotification;

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
        'reference' => 'FAC-2026-099',
        'total_ttc' => 5000.00,
        'due_date' => now()->subDays(15),
    ]);
});

test('notification uses mail and database channels', function () {
    $notification = new RelanceNotification($this->invoice, 1, 15);

    expect($notification->via($this->contact))->toBe(['mail', 'database']);
});

test('level 1 mail has correct subject and greeting', function () {
    $notification = new RelanceNotification($this->invoice, 1, 15);

    $mail = $notification->toMail($this->contact);

    expect($mail->subject)->toContain('Rappel : Paiement en attente');
    expect($mail->subject)->toContain('FAC-2026-099');
    expect($mail->greeting)->toBe('Rappel de paiement');
});

test('level 1 mail contains days late info', function () {
    $notification = new RelanceNotification($this->invoice, 1, 15);

    $mail = $notification->toMail($this->contact);

    $hasDaysLate = collect($mail->introLines)->contains(fn ($line) => str_contains($line, '15 jours'));
    expect($hasDaysLate)->toBeTrue();
});

test('level 2 mail has correct subject and greeting', function () {
    $notification = new RelanceNotification($this->invoice, 2, 30);

    $mail = $notification->toMail($this->contact);

    expect($mail->subject)->toContain('Mise en demeure de paiement');
    expect($mail->subject)->toContain('FAC-2026-099');
    expect($mail->greeting)->toBe('Mise en demeure');
});

test('level 2 mail contains mise en demeure text', function () {
    $notification = new RelanceNotification($this->invoice, 2, 30);

    $mail = $notification->toMail($this->contact);

    $hasText = collect($mail->introLines)->contains(
        fn ($line) => str_contains($line, 'mise en demeure') || str_contains($line, 'délai de 8 jours')
    );
    expect($hasText)->toBeTrue();
});

test('level 3 mail has correct subject and greeting', function () {
    $notification = new RelanceNotification($this->invoice, 3, 45);

    $mail = $notification->toMail($this->contact);

    expect($mail->subject)->toContain('Dernière relance avant contentieux');
    expect($mail->subject)->toContain('FAC-2026-099');
    expect($mail->greeting)->toBe('Dernière relance');
});

test('level 3 mail contains legal warning text', function () {
    $notification = new RelanceNotification($this->invoice, 3, 45);

    $mail = $notification->toMail($this->contact);

    $hasText = collect($mail->introLines)->contains(
        fn ($line) => str_contains($line, 'procédure judiciaire') || str_contains($line, 'poursuites')
    );
    expect($hasText)->toBeTrue();
});

test('mail contains invoice reference and amount in all levels', function ($level) {
    $notification = new RelanceNotification($this->invoice, $level, 20);

    $mail = $notification->toMail($this->contact);

    $hasRef = collect($mail->introLines)->contains(fn ($line) => str_contains($line, 'FAC-2026-099'));
    $hasAmount = collect($mail->introLines)->contains(fn ($line) => str_contains($line, '5 000,00'));

    expect($hasRef)->toBeTrue();
    expect($hasAmount)->toBeTrue();
})->with([1, 2, 3]);

test('mail has correct action url for all levels', function ($level) {
    $notification = new RelanceNotification($this->invoice, $level, 10);

    $mail = $notification->toMail($this->contact);

    expect($mail->actionUrl)->toContain("/customer/customer-invoices/{$this->invoice->id}");
    expect($mail->actionText)->toBe('Consulter la facture');
})->with([1, 2, 3]);

test('database notification has correct title for all levels', function ($level) {
    $notification = new RelanceNotification($this->invoice, $level, 20);

    $databaseData = $notification->toDatabase($this->contact);

    expect($databaseData['body'])->toContain('FAC-2026-099');
    expect($databaseData['body'])->toContain('20 jour(s) de retard');
})->with([1, 2, 3]);

test('toArray returns empty array', function () {
    $notification = new RelanceNotification($this->invoice, 1, 10);

    expect($notification->toArray($this->contact))->toBe([]);
});
