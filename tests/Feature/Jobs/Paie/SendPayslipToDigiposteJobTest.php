<?php

use App\Jobs\Paie\SendPayslipToDigiposteJob;
use App\Models\Paie\Payslip;
use App\Services\Paie\DigiposteService;
use Illuminate\Http\Client\RequestException;
use Mockery\MockInterface;

it('dispatches job and deposits payslip', function () {
    $payslip = \Mockery::mock(Payslip::class);
    
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
    $payslip = \Mockery::mock(Payslip::class);
    
    $this->mock(DigiposteService::class, function (MockInterface $mock) use ($payslip) {
        $mock->shouldReceive('depositPayslip')
             ->once()
             ->with($payslip)
             ->andReturn(false);
    });

    $job = new SendPayslipToDigiposteJob($payslip);
    $job->failOnTimeout = false; // Just to make sure we can test fail() call if needed

    // Pest doesn't easily mock the job's $this->fail(), but we know it throws an exception in standard Laravel queue testing
    // wait, $this->fail() throws an exception or just marks as failed? It throws a ManuallyFailedException
    expect(fn() => app()->call([$job, 'handle']))->toThrow(\Exception::class, 'Deposit returned false for non-transient error');
});

it('bubbles up exceptions for transient errors', function () {
    $payslip = \Mockery::mock(Payslip::class);
    
    $this->mock(DigiposteService::class, function (MockInterface $mock) use ($payslip) {
        $mock->shouldReceive('depositPayslip')
             ->once()
             ->with($payslip)
             ->andThrow(new \Exception('Transient HTTP Error'));
    });

    $job = new SendPayslipToDigiposteJob($payslip);

    expect(fn() => app()->call([$job, 'handle']))->toThrow(\Exception::class, 'Transient HTTP Error');
});
