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

    public int $tries = 3;

    public array $backoff = [60, 300, 3600];

    public int $timeout = 30;

    public function __construct(public Payslip $payslip) {}

    public function handle(DigiposteService $digiposteService): void
    {
        if (! $digiposteService->depositPayslip($this->payslip)) {
            $this->fail(new \Exception('Deposit returned false for non-transient error'));
        }
    }
}
