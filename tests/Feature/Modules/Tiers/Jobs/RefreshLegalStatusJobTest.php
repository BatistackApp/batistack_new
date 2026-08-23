<?php

use App\Enums\Tiers\LegalStatus;
use App\Jobs\Tiers\RefreshLegalStatusJob;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Notifications\Tiers\LegalStatusAlertNotification;
use App\Services\Tiers\PappersService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('syncs financial and legal data and notifies admins when status becomes collective proceeding', function () {
    Notification::fake();

    $admin = User::factory()->create(['is_admin' => true]);
    $regularUser = User::factory()->create(['is_admin' => false]);

    $thirdParty = ThirdParty::factory()->create([
        'is_active' => true,
        'siren' => '123456789',
        'legal_status' => LegalStatus::SAIN,
    ]);

    $mockPappers = Mockery::mock(PappersService::class);
    $mockPappers->shouldReceive('syncFinancialData')
        ->once()
        ->with(Mockery::on(fn ($tp) => $tp->id === $thirdParty->id))
        ->andReturnUsing(function ($tp) {
            $tp->update([
                'legal_status' => LegalStatus::REDRESSEMENT_JUDICIAIRE,
                'last_financial_sync_at' => now(),
            ]);
            return true;
        });

    $job = new RefreshLegalStatusJob($thirdParty);
    $job->handle($mockPappers);

    Notification::assertSentTo(
        [$admin],
        LegalStatusAlertNotification::class,
        function ($notification) use ($thirdParty, $admin) {
            $arrayData = $notification->toArray($admin);
            return $arrayData['third_party_id'] === $thirdParty->id
                && $arrayData['new_status'] === LegalStatus::REDRESSEMENT_JUDICIAIRE->value
                && $arrayData['previous_status'] === LegalStatus::SAIN->value;
        }
    );

    Notification::assertNotSentTo([$regularUser], LegalStatusAlertNotification::class);
});

it('does not send notification when status remains healthy or unchanged', function () {
    Notification::fake();

    $admin = User::factory()->create(['is_admin' => true]);

    $thirdParty = ThirdParty::factory()->create([
        'is_active' => true,
        'siren' => '123456789',
        'legal_status' => LegalStatus::SAIN,
    ]);

    $mockPappers = Mockery::mock(PappersService::class);
    $mockPappers->shouldReceive('syncFinancialData')
        ->once()
        ->with(Mockery::on(fn ($tp) => $tp->id === $thirdParty->id))
        ->andReturnUsing(function ($tp) {
            $tp->update([
                'legal_status' => LegalStatus::SAIN,
                'last_financial_sync_at' => now(),
            ]);
            return true;
        });

    $job = new RefreshLegalStatusJob($thirdParty);
    $job->handle($mockPappers);

    Notification::assertNothingSent();
});
