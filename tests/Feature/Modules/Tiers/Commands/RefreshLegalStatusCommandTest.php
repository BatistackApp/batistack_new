<?php

use App\Jobs\Tiers\RefreshLegalStatusJob;
use App\Models\Tiers\ThirdParty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('dispatches refresh legal status jobs for eligible third parties', function () {
    Queue::fake([RefreshLegalStatusJob::class]);

    // Eligible: active, has SIREN, never synced
    $thirdParty1 = ThirdParty::factory()->create([
        'is_active' => true,
        'siren' => '123456789',
        'last_financial_sync_at' => null,
    ]);

    // Eligible: active, has SIRET, synced 40 days ago
    $thirdParty2 = ThirdParty::factory()->create([
        'is_active' => true,
        'siret' => '98765432100012',
        'last_financial_sync_at' => now()->subDays(40),
    ]);

    // Not eligible: synced recently (5 days ago)
    $thirdPartyRecent = ThirdParty::factory()->create([
        'is_active' => true,
        'siren' => '111222333',
        'last_financial_sync_at' => now()->subDays(5),
    ]);

    // Not eligible: inactive
    $thirdPartyInactive = ThirdParty::factory()->create([
        'is_active' => false,
        'siren' => '444555666',
        'last_financial_sync_at' => null,
    ]);

    // Not eligible: no SIREN / SIRET
    $thirdPartyNoSiren = ThirdParty::factory()->create([
        'is_active' => true,
        'siren' => null,
        'siret' => null,
        'last_financial_sync_at' => null,
    ]);

    $this->artisan('tiers:refresh-legal-status', ['--days' => 30, '--limit' => 50])
        ->assertSuccessful();

    Queue::assertPushed(RefreshLegalStatusJob::class, function ($job) use ($thirdParty1) {
        return $job->thirdParty->id === $thirdParty1->id;
    });

    Queue::assertPushed(RefreshLegalStatusJob::class, function ($job) use ($thirdParty2) {
        return $job->thirdParty->id === $thirdParty2->id;
    });

    Queue::assertNotPushed(RefreshLegalStatusJob::class, function ($job) use ($thirdPartyRecent) {
        return $job->thirdParty->id === $thirdPartyRecent->id;
    });

    Queue::assertNotPushed(RefreshLegalStatusJob::class, function ($job) use ($thirdPartyInactive) {
        return $job->thirdParty->id === $thirdPartyInactive->id;
    });

    Queue::assertNotPushed(RefreshLegalStatusJob::class, function ($job) use ($thirdPartyNoSiren) {
        return $job->thirdParty->id === $thirdPartyNoSiren->id;
    });
});

it('respects limit and type options', function () {
    Queue::fake([RefreshLegalStatusJob::class]);

    ThirdParty::factory()->count(5)->create([
        'is_active' => true,
        'siren' => '123456789',
        'type' => \App\Enums\Tiers\ThirdPartyType::SUBCONTRACTOR,
        'last_financial_sync_at' => null,
    ]);

    ThirdParty::factory()->count(3)->create([
        'is_active' => true,
        'siren' => '987654321',
        'type' => \App\Enums\Tiers\ThirdPartyType::CUSTOMER,
        'last_financial_sync_at' => null,
    ]);

    $this->artisan('tiers:refresh-legal-status', [
        '--days' => 30,
        '--limit' => 2,
        '--type' => \App\Enums\Tiers\ThirdPartyType::SUBCONTRACTOR->value,
    ])->assertSuccessful();

    Queue::assertPushed(RefreshLegalStatusJob::class, 2);
});
