<?php

namespace App\Jobs\Paie;

use App\Models\Paie\Payslip;
use App\Services\Paie\DigiposteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPayslipToDigiposteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Payslip $payslip)
    {
    }

    public function handle(DigiposteService $digiposteService): void
    {
        $digiposteService->depositPayslip($this->payslip);
    }
}
