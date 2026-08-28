<?php

use App\Models\Paie\DsnSubmission;
use App\Models\User;
use App\Notifications\Paie\DsnExportedNotification;
use App\Notifications\Paie\DsnExportReadyNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;

uses(RefreshDatabase::class);

it('sends DsnExportedNotification via mail and database', function () {
    $user = User::factory()->create();
    $submission = DsnSubmission::factory()->create([
        'period' => '2026-07',
        'payslips_count' => 5,
        'total_gross' => 15000,
    ]);

    $notification = new DsnExportedNotification($submission);

    expect($notification->via($user))->toBe(['mail', 'database']);
});

it('builds DsnExportedNotification mail correctly', function () {
    $user = User::factory()->create();
    $submission = DsnSubmission::factory()->create([
        'period' => '2026-07',
        'payslips_count' => 5,
        'total_gross' => 15000,
    ]);

    $notification = new DsnExportedNotification($submission);
    $mail = $notification->toMail($user);

    expect($mail)->toBeInstanceOf(MailMessage::class);
    expect($mail->subject)->toContain('2026-07');
});

it('builds DsnExportedNotification array correctly', function () {
    $user = User::factory()->create();
    $submission = DsnSubmission::factory()->create([
        'period' => '2026-07',
        'payslips_count' => 5,
    ]);

    $notification = new DsnExportedNotification($submission);
    $array = $notification->toArray($user);

    expect($array)->toHaveKeys(['dsn_submission_id', 'period', 'payslips_count', 'message']);
    expect($array['period'])->toBe('2026-07');
    expect($array['payslips_count'])->toBe(5);
});

it('sends DsnExportReadyNotification via mail and database', function () {
    $user = User::factory()->create();

    $notification = new DsnExportReadyNotification('2026-07', 10, 30000.0);

    expect($notification->via($user))->toBe(['mail', 'database']);
});

it('builds DsnExportReadyNotification mail correctly', function () {
    $user = User::factory()->create();

    $notification = new DsnExportReadyNotification('2026-07', 10, 30000.0);
    $mail = $notification->toMail($user);

    expect($mail)->toBeInstanceOf(MailMessage::class);
    expect($mail->subject)->toContain('2026-07');
});

it('builds DsnExportReadyNotification array correctly', function () {
    $user = User::factory()->create();

    $notification = new DsnExportReadyNotification('2026-07', 10, 30000.0);
    $array = $notification->toArray($user);

    expect($array)->toHaveKeys(['period', 'payslips_count', 'total_gross', 'message']);
    expect($array['period'])->toBe('2026-07');
    expect($array['payslips_count'])->toBe(10);
    expect($array['total_gross'])->toBe(30000.0);
});
