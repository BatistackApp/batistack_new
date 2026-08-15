<?php

use App\Enums\Interventions\MaintenanceContractFrequency;
use App\Models\Interventions\ClientEquipment;
use App\Models\Interventions\MaintenanceContract;
use App\Models\Tiers\ThirdParty;
use App\Notifications\Interventions\MaintenanceContractReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('routes reminders through the mail channel', function () {
    $contract = MaintenanceContract::factory()->create();
    $notification = new MaintenanceContractReminderNotification($contract);

    expect($notification->via(new AnonymousNotifiable))->toBe(['mail']);
});

it('builds a reminder mail with the equipment and due date', function () {
    $client = ThirdParty::factory()->create();
    $equipment = ClientEquipment::factory()->create(['third_party_id' => $client->id, 'name' => 'Groupe froid', 'brand' => 'Carrier']);

    $contract = MaintenanceContract::factory()->create([
        'third_party_id' => $client->id,
        'client_equipment_id' => $equipment->id,
        'name' => 'Contrat annuel',
        'frequency' => MaintenanceContractFrequency::QUARTERLY,
        'next_due_date' => Carbon::parse('2026-09-15'),
    ]);

    $mail = (new MaintenanceContractReminderNotification($contract))->toMail(new AnonymousNotifiable);

    expect($mail->subject)->toBe('Entretien préventif à venir - Groupe froid')
        ->and($mail->greeting)->toBe('Bonjour,')
        ->and($mail->introLines)->toContain('Dans le cadre de votre **contrat d\'entretien** **Contrat annuel** (réf. '.$contract->reference.'), une intervention de maintenance préventive est planifiée.')
        ->and($mail->introLines)->toContain('- **Équipement :** Groupe froid (Carrier)')
        ->and($mail->introLines)->toContain('- **Échéance :** 15/09/2026')
        ->and($mail->introLines)->toContain('- **Fréquence :** Trimestriel')
        ->and($mail->actionUrl)->toBe(url('/customer'))
        ->and($mail->actionText)->toBe('Voir mon espace client');
});

it('handles a contract without a due date', function () {
    $client = ThirdParty::factory()->create();
    $equipment = ClientEquipment::factory()->create(['third_party_id' => $client->id, 'name' => 'Groupe froid']);

    $contract = MaintenanceContract::factory()->create([
        'third_party_id' => $client->id,
        'client_equipment_id' => $equipment->id,
        'next_due_date' => null,
    ]);

    $mail = (new MaintenanceContractReminderNotification($contract))->toMail(new AnonymousNotifiable);

    expect($mail->subject)->toBe('Entretien préventif à venir - Groupe froid')
        ->and($mail->introLines)->toContain('- **Échéance :** à définir');
});
