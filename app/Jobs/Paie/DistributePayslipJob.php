<?php

namespace App\Jobs\Paie;

use App\Mail\Paie\PayslipAvailableMail;
use App\Models\Paie\Payslip;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class DistributePayslipJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payslip $payslip
    ) {}

    public function handle(): void
    {
        $employee = $this->payslip->employee;
        $user = $employee?->user;

        if (!$employee || !$user) {
            return;
        }

        // 1. Envoyer la notification Push / Filament Database
        Notification::make()
            ->title('Nouveau bulletin de salaire')
            ->body('Votre bulletin de salaire pour la période ' . $this->payslip->period . ' est disponible.')
            ->success()
            ->sendToDatabase($user);

        // 2. Envoyer l'email
        if ($employee->email) {
            Mail::to($employee->email)->send(new PayslipAvailableMail($this->payslip));
        }
    }
}
