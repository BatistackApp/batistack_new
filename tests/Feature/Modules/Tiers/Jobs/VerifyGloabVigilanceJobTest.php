<?php

use App\Jobs\Tiers\VerifyGloabVigilanceJob;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Notifications\Tiers\SubcontractorVigilanceReminderNotification;
use App\Notifications\Tiers\VigilanceExpirationNotification;
use App\Services\Tiers\VigilanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

use Illuminate\Support\Facades\Queue;

it('dispatches vigilance expiration notifications to admins and subcontractors via primary contact', function () {
    Notification::fake();
    Queue::fake([VerifyGloabVigilanceJob::class]);

    // Create admin user
    $admin = User::factory()->create(['is_admin' => true]);

    // Create subcontractor with no direct email but a primary contact
    $subcontractor = ThirdParty::factory()->create([
        'type' => \App\Enums\Tiers\ThirdPartyType::SUBCONTRACTOR,
        'is_active' => true,
        'email' => null, // No generic email
    ]);

    // Add primary contact with email
    $contact = Contact::factory()->create([
        'third_party_id' => $subcontractor->id,
        'is_primary' => true,
        'email' => 'subcontractor@example.com',
    ]);

    // Mock the VigilanceService to return non-compliant
    $mockService = Mockery::mock(VigilanceService::class);
    $mockService->shouldReceive('scanCompliance')
        ->once()
        ->andReturn([
            'compliant' => false,
            'issues' => ['URSSAF expired'],
        ]);

    $job = new VerifyGloabVigilanceJob();
    $job->handle($mockService);

    // Assert internal notification sent to admin
    Notification::assertSentTo(
        [$admin],
        VigilanceExpirationNotification::class
    );

    // Assert external reminder sent to primary contact
    Notification::assertSentOnDemand(
        SubcontractorVigilanceReminderNotification::class,
        function ($notification, $channels, $notifiable) {
            return $notifiable->routes['mail'] === 'subcontractor@example.com';
        }
    );
});

it('updates compliant status and sends no notifications when subcontractor is compliant', function () {
    Notification::fake();
    Queue::fake([VerifyGloabVigilanceJob::class]);

    $subcontractor = ThirdParty::factory()->create([
        'type' => \App\Enums\Tiers\ThirdPartyType::SUBCONTRACTOR,
        'is_active' => true,
    ]);

    $mockService = Mockery::mock(VigilanceService::class);
    $mockService->shouldReceive('scanCompliance')
        ->once()
        ->andReturn([
            'compliant' => true,
            'issues' => [],
        ]);

    $job = new VerifyGloabVigilanceJob();
    $job->handle($mockService);

    Notification::assertNothingSent();

    $subcontractor->refresh();
    expect($subcontractor->compliant_status)->toBe([
        'compliant' => true,
        'issues' => [],
    ]);
});

