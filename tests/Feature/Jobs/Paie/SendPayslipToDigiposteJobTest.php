<?php

use App\Jobs\Paie\SendPayslipToDigiposteJob;
use App\Models\Paie\Payslip;
use App\Services\Paie\DigiposteService;
use Mockery\MockInterface;

it('dispatches job and deposits payslip', function () {
    $payslip = \Mockery::mock(Payslip::class);
    
    $this->mock(DigiposteService::class, function (MockInterface $mock) use ($payslip) {
        $mock->shouldReceive('depositPayslip')
             ->once()
             ->with($payslip);
    });

    $job = new SendPayslipToDigiposteJob($payslip);
    app()->call([$job, 'handle']);
});
