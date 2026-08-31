<?php

use App\Jobs\Paie\SendPayslipToDigiposteJob;
use App\Models\Paie\Payslip;
use App\Services\Paie\DigiposteService;
use Mockery\MockInterface;

it('calls digiposte service to deposit payslip', function () {
    $payslip = Payslip::factory()->create();

    $this->mock(DigiposteService::class, function (MockInterface $mock) use ($payslip) {
        $mock->shouldReceive('depositPayslip')
            ->once()
            ->withArgs(function ($arg) use ($payslip) {
                return $arg->id === $payslip->id;
            })
            ->andReturn(true);
    });

    SendPayslipToDigiposteJob::dispatch($payslip);
});

it('dispatches job and deposits payslip', function () {
    $payslip = Mockery::mock(Payslip::class);

    $this->mock(DigiposteService::class, function (MockInterface $mock) use ($payslip) {
        $mock->shouldReceive('depositPayslip')
            ->once()
            ->with($payslip)
            ->andReturn(true);
    });

    $job = new SendPayslipToDigiposteJob($payslip);
    app()->call([$job, 'handle']);
});

it('throws exception when deposit returns false', function () {
    $payslip = Mockery::mock(Payslip::class);

    $this->mock(DigiposteService::class, function (MockInterface $mock) use ($payslip) {
        $mock->shouldReceive('depositPayslip')
            ->once()
            ->with($payslip)
            ->andReturn(false);
    });

    $job = new SendPayslipToDigiposteJob($payslip);
    $job->withFakeQueueInteractions();

    app()->call([$job, 'handle']);

    $job->assertFailed();
});

it('bubbles up exceptions for transient errors', function () {
    $payslip = Mockery::mock(Payslip::class);

    $this->mock(DigiposteService::class, function (MockInterface $mock) use ($payslip) {
        $mock->shouldReceive('depositPayslip')
            ->once()
            ->with($payslip)
            ->andThrow(new Exception('Transient HTTP Error'));
    });

    $job = new SendPayslipToDigiposteJob($payslip);

    expect(fn () => app()->call([$job, 'handle']))->toThrow(Exception::class, 'Transient HTTP Error');
});
