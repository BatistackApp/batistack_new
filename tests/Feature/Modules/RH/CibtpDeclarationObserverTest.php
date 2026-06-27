<?php

use App\Models\Chantiers\Chantier;
use App\Models\RH\CibtpDeclaration;
use App\Models\RH\Employee;
use App\Models\User;
use App\Models\Tiers\ThirdParty;
use App\Notifications\Commerce\ChantierDelayAvenantNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('it shifts end date and sends notifications when cibtp is validated', function () {
    Notification::fake();

    $manager = Employee::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $client = ThirdParty::factory()->create(['email' => 'client@test.com']);
    
    $chantier = Chantier::factory()->create([
        'manager_id' => $manager->id,
        'client_id' => $client->id,
        'end_date_preview' => '2026-07-01',
    ]);

    $declaration = CibtpDeclaration::create([
        'chantier_id' => $chantier->id,
        'date' => '2026-06-25',
        'status' => 'draft',
        'total_lost_hours' => 16, // 16 / 8 = 2 days
    ]);

    // Action : update to validated
    $declaration->update(['status' => 'validated']);

    // Assert : date shifted
    $chantier->refresh();
    expect($chantier->end_date_preview->format('Y-m-d'))->toBe('2026-07-03');

    // Assert : Manager notified
    Notification::assertSentTo(
        [$manager],
        ChantierDelayAvenantNotification::class
    );

    // Assert : Admin notified
    Notification::assertSentTo(
        [$admin],
        ChantierDelayAvenantNotification::class
    );
    
    // Assert : Client notified via AnonymousNotifiable
    Notification::assertSentOnDemand(
        ChantierDelayAvenantNotification::class,
        function ($notification, $channels, $notifiable) use ($client) {
            return $notifiable->routes['mail'] === 'client@test.com';
        }
    );
});
