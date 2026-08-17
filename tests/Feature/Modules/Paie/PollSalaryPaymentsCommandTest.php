<?php

use App\Enums\Paie\SalaryPaymentStatus;
use App\Models\Paie\SalaryPaymentRun;
use App\Services\Paie\SalaryPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

function pollableRun(array $overrides = []): SalaryPaymentRun
{
    return SalaryPaymentRun::create(array_merge([
        'period' => '2026-07',
        'total_amount' => 1350,
        'count' => 1,
        'status' => SalaryPaymentStatus::AWAITING_VALIDATION,
        'idempotency_key' => 'key-'.uniqid(),
        'bridge_payment_request_id' => 'req-1',
    ], $overrides));
}

function pollService(): MockInterface
{
    $service = Mockery::mock(SalaryPaymentService::class);
    app()->instance(SalaryPaymentService::class, $service);

    return $service;
}

it('polls pending runs and reports the count', function () {
    pollableRun();
    pollableRun(['status' => SalaryPaymentStatus::PROCESSING]);

    $service = pollService();
    $service->shouldReceive('pollRun')->twice();

    $exit = Artisan::call('paie:poll-salary-payments');

    expect($exit)->toBe(0)
        ->and(Artisan::output())->toContain('2 run(s) vérifié(s)');
});

it('counts failures and returns a failure exit code', function () {
    pollableRun();

    $service = pollService();
    $service->shouldReceive('pollRun')->once()->andThrow(new RuntimeException('boom'));

    $exit = Artisan::call('paie:poll-salary-payments');

    expect($exit)->toBe(1)
        ->and(Artisan::output())->toContain('1 en échec');
});

it('ignores runs without a bridge request id or in a terminal state', function () {
    pollableRun(['bridge_payment_request_id' => null]);
    pollableRun(['status' => SalaryPaymentStatus::SUCCEEDED]);
    pollableRun(['status' => SalaryPaymentStatus::FAILED]);

    $service = pollService();
    $service->shouldNotReceive('pollRun');

    $exit = Artisan::call('paie:poll-salary-payments');

    expect($exit)->toBe(0)
        ->and(Artisan::output())->toContain('0 run(s) vérifié(s)');
});
