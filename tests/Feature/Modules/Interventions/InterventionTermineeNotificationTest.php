<?php

use App\Models\Chantiers\Chantier;
use App\Models\Core\Company;
use App\Models\Interventions\Intervention;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Notifications\Customer\InterventionTermineeNotification;

beforeEach(function () {
    Company::factory()->create();
    $this->customer = ThirdParty::factory()->create();
    $this->contact = Contact::factory()->create([
        'third_party_id' => $this->customer->id,
        'is_primary' => true,
    ]);

    $this->chantier = Chantier::factory()->create(['client_id' => $this->customer->id]);

    $this->intervention = Intervention::factory()->create([
        'third_party_id' => $this->customer->id,
        'chantier_id' => $this->chantier->id,
        'reference' => 'INT-2026-0005',
        'completed_at' => now()->subHours(2),
    ]);
});

test('notification uses mail and database channels', function () {
    $notification = new InterventionTermineeNotification($this->intervention);

    expect($notification->via($this->contact))->toBe(['mail', 'database']);
});

test('mail notification has correct subject', function () {
    $notification = new InterventionTermineeNotification($this->intervention);

    $mail = $notification->toMail($this->contact);

    expect($mail->subject)->toBe('Intervention terminée : INT-2026-0005');
});

test('mail notification has correct greeting', function () {
    $notification = new InterventionTermineeNotification($this->intervention);

    $mail = $notification->toMail($this->contact);

    expect($mail->greeting)->toBe('Intervention terminée');
});

test('mail notification contains reference', function () {
    $notification = new InterventionTermineeNotification($this->intervention);

    $mail = $notification->toMail($this->contact);

    $hasRef = collect($mail->introLines)->contains(fn ($line) => str_contains($line, 'INT-2026-0005'));
    expect($hasRef)->toBeTrue();
});

test('mail notification contains type label', function () {
    $notification = new InterventionTermineeNotification($this->intervention);

    $mail = $notification->toMail($this->contact);

    $hasType = collect($mail->introLines)->contains(fn ($line) => str_contains($line, 'Type'));
    expect($hasType)->toBeTrue();
});

test('mail notification contains chantier reference', function () {
    $notification = new InterventionTermineeNotification($this->intervention);

    $mail = $notification->toMail($this->contact);

    $hasChantier = collect($mail->introLines)->contains(fn ($line) => str_contains($line, 'Chantier'));
    expect($hasChantier)->toBeTrue();
});

test('mail notification contains completed date', function () {
    $notification = new InterventionTermineeNotification($this->intervention);

    $mail = $notification->toMail($this->contact);

    $hasDate = collect($mail->introLines)->contains(
        fn ($line) => str_contains($line, $this->intervention->completed_at->format('d/m/Y'))
    );
    expect($hasDate)->toBeTrue();
});

test('mail notification has correct action url', function () {
    $notification = new InterventionTermineeNotification($this->intervention);

    $mail = $notification->toMail($this->contact);

    expect($mail->actionUrl)->toContain("/customer/interventions/{$this->intervention->id}");
    expect($mail->actionText)->toBe('Voir le rapport');
});

test('database notification has correct title', function () {
    $notification = new InterventionTermineeNotification($this->intervention);

    $databaseData = $notification->toDatabase($this->contact);

    expect($databaseData['title'])->toContain('INT-2026-0005');
    expect($databaseData['title'])->toContain('terminée');
});

test('database notification body contains completed date', function () {
    $notification = new InterventionTermineeNotification($this->intervention);

    $databaseData = $notification->toDatabase($this->contact);

    expect($databaseData['body'])->toContain($this->intervention->completed_at->format('d/m/Y'));
});

test('toArray returns empty array', function () {
    $notification = new InterventionTermineeNotification($this->intervention);

    expect($notification->toArray($this->contact))->toBe([]);
});
