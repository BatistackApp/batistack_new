<?php

use App\Jobs\Laser\SyncLaserAccountingJob;
use App\Models\Accounting\AccountingSync;
use App\Models\Laser\LaserInvoice;
use App\Models\User;
use App\Notifications\Accounting\LaserAccountingSyncFailedNotification;
use App\Services\Laser\LaserInvoiceAccountingService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Bus::fake();
});

it('handles invoice synchronization through the accounting service', function () {
    $invoice = LaserInvoice::factory()->create([
        'status' => 'validated',
        'total_ht' => 100,
        'total_tva' => 20,
        'total_ttc' => 120,
    ]);
    $service = \Mockery::mock(LaserInvoiceAccountingService::class);
    $service->expects('syncInvoice')->once()->with(\Mockery::on(fn ($value) => $value->is($invoice)));
    app()->instance(LaserInvoiceAccountingService::class, $service);

    (new SyncLaserAccountingJob('invoice', $invoice->id))->handle($service);

    expect(AccountingSync::where('syncable_id', $invoice->id)->value('attempts'))->toBe(1);
});

it('marks synchronization as failed and notifies users after final failure', function () {
    Notification::fake();
    $invoice = LaserInvoice::factory()->create(['status' => 'validated']);
    $user = User::factory()->create();
    $job = new SyncLaserAccountingJob('invoice', $invoice->id);
    $exception = new RuntimeException('Accounting unavailable');

    $job->failed($exception);

    expect(AccountingSync::where('syncable_id', $invoice->id)->value('status'))->toBe('failed');
    Notification::assertSentTo($user, LaserAccountingSyncFailedNotification::class);
});

it('exposes four total attempts and a sixty second backoff', function () {
    $job = new SyncLaserAccountingJob('invoice', 1);

    expect($job->tries)->toBe(4)
        ->and($job->backoff)->toBe(60);
});
