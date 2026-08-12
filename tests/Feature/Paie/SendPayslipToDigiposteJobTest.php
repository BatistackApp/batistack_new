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
