<?php

use App\Enums\Paie\SalaryPaymentStatus;
use App\Jobs\Paie\InitiateSalaryPaymentRunJob;
use App\Models\Paie\SalaryPaymentRun;
use App\Services\Paie\SalaryPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

function jobRun(array $overrides = []): SalaryPaymentRun
{
    return SalaryPaymentRun::create(array_merge([
        'period' => '2026-07',
        'total_amount' => 1350,
        'count' => 1,
        'status' => SalaryPaymentStatus::PENDING,
        'idempotency_key' => 'key-'.uniqid(),
    ], $overrides));
}

it('logs when a new bridge request is created', function () {
    $run = jobRun();
    $service = Mockery::mock(SalaryPaymentService::class);
    $service->shouldReceive('initiateRun')->once()->with($run)->andReturn(true);

    Log::spy();
    (new InitiateSalaryPaymentRunJob($run))->handle($service);
    Log::shouldHaveReceived('info')->once();
});

it('does not log when no new bridge request is created', function () {
    $run = jobRun();
    $service = Mockery::mock(SalaryPaymentService::class);
    $service->shouldReceive('initiateRun')->once()->andReturn(false);

    Log::spy();
    (new InitiateSalaryPaymentRunJob($run))->handle($service);
    Log::shouldNotHaveReceived('info');
});

it('keeps the run reconciliable and does not mark it failed on a transport error', function () {
    $run = jobRun();
    $service = Mockery::mock(SalaryPaymentService::class);
    $service->shouldReceive('initiateRun')->once()->andThrow(new RuntimeException('network boom'));

    (new InitiateSalaryPaymentRunJob($run))->handle($service);

    expect($run->fresh()->status)->toBe(SalaryPaymentStatus::PENDING);
});
