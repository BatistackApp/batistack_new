<?php

use App\Enums\Tiers\LegalStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Jobs\Tiers\RefreshLegalStatusJob;
use App\Jobs\Tiers\VerifyGloabVigilanceJob;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Notifications\Tiers\LegalStatusChangedNotification;
use App\Services\Tiers\PappersService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Bus::fake([VerifyGloabVigilanceJob::class]);
});

it('refreshes legal status for active third parties with SIREN', function () {
    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
        'is_active' => true,
        'siren' => '123456789',
        'legal_status' => null,
        'last_financial_sync_at' => null,
    ]);

    $mockService = Mockery::mock(PappersService::class);
    $mockService->shouldReceive('syncFinancialData')
        ->once()
        ->andReturn(true);

    $job = new RefreshLegalStatusJob;
    $job->handle($mockService);

    Notification::assertNothingSent();
});

it('skips third parties recently synced within 7 days', function () {
    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
        'is_active' => true,
        'siren' => '123456789',
        'last_financial_sync_at' => Carbon::now()->subDays(3),
    ]);

    $mockService = Mockery::mock(PappersService::class);
    $mockService->shouldReceive('syncFinancialData')
        ->never();

    $job = new RefreshLegalStatusJob;
    $job->handle($mockService);

    Notification::assertNothingSent();
});

it('processes third parties with null last_financial_sync_at', function () {
    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
        'is_active' => true,
        'siren' => '123456789',
        'legal_status' => null,
        'last_financial_sync_at' => null,
    ]);

    $mockService = Mockery::mock(PappersService::class);
    $mockService->shouldReceive('syncFinancialData')
        ->once()
        ->andReturn(true);

    $job = new RefreshLegalStatusJob;
    $job->handle($mockService);

    Notification::assertNothingSent();
});

it('logs warning and continues on API failure', function () {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(function ($message) {
            return str_contains($message, 'échec de la synchro');
        });
    Log::shouldReceive('info')->once();

    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
        'is_active' => true,
        'siren' => '123456789',
        'last_financial_sync_at' => null,
    ]);

    $mockService = Mockery::mock(PappersService::class);
    $mockService->shouldReceive('syncFinancialData')
        ->once()
        ->andReturn(false);

    $job = new RefreshLegalStatusJob;
    $job->handle($mockService);

    Notification::assertNothingSent();
});

it('sends notification when status changes to redressement judiciaire', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
        'is_active' => true,
        'siren' => '123456789',
        'legal_status' => LegalStatus::SAIN,
        'last_financial_sync_at' => null,
    ]);

    $mockService = Mockery::mock(PappersService::class);
    $mockService->shouldReceive('syncFinancialData')
        ->once()
        ->andReturnUsing(function () use ($thirdParty) {
            $thirdParty->update(['legal_status' => LegalStatus::REDRESSEMENT_JUDICIAIRE]);

            return true;
        });

    Log::shouldReceive('info')->times(2);

    $job = new RefreshLegalStatusJob;
    $job->handle($mockService);

    Notification::assertSentTo(
        [$admin],
        LegalStatusChangedNotification::class
    );
});

it('sends notification when status changes to liquidation judiciaire', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
        'is_active' => true,
        'siren' => '123456789',
        'legal_status' => LegalStatus::SAIN,
        'last_financial_sync_at' => null,
    ]);

    $mockService = Mockery::mock(PappersService::class);
    $mockService->shouldReceive('syncFinancialData')
        ->once()
        ->andReturnUsing(function () use ($thirdParty) {
            $thirdParty->update(['legal_status' => LegalStatus::LIQUIDATION_JUDICIAIRE]);

            return true;
        });

    Log::shouldReceive('info')->times(2);

    $job = new RefreshLegalStatusJob;
    $job->handle($mockService);

    Notification::assertSentTo(
        [$admin],
        LegalStatusChangedNotification::class
    );
});

it('does not send notification when status stays sain', function () {
    $thirdParty = ThirdParty::factory()->create([
        'type' => ThirdPartyType::SUBCONTRACTOR,
        'is_active' => true,
        'siren' => '123456789',
        'legal_status' => LegalStatus::SAIN,
        'last_financial_sync_at' => null,
    ]);

    $mockService = Mockery::mock(PappersService::class);
    $mockService->shouldReceive('syncFinancialData')
        ->once()
        ->andReturn(true);

    Log::shouldReceive('info')->once();

    $job = new RefreshLegalStatusJob;
    $job->handle($mockService);

    Notification::assertNothingSent();
});
