<?php

namespace App\Filament\Paie\Widgets;

use App\Enums\Paie\AdvancePaymentStatus;
use App\Enums\Paie\PayslipStatus;
use App\Filament\Paie\Resources\Paie\AdvancePayments\AdvancePaymentResource;
use App\Filament\Paie\Resources\Paie\Payslips\PayslipResource;
use App\Models\Paie\AdvancePayment;
use App\Models\Paie\Payslip;
use LaBoiteACode\FilamentDashboardWidgets\Data\Detail;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\DetailListWidget;

class PendingPaymentsDetailWidget extends DetailListWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'À Payer & Acomptes';
    }

    protected function getDetails(): array
    {
        $details = [];

        // 1. Acomptes en attente
        $advances = AdvancePayment::with('employee')
            ->where('status', AdvancePaymentStatus::PENDING)
            ->orderBy('request_date', 'asc')
            ->get();

        foreach ($advances as $advance) {
            $details[] = Detail::make('Acompte - '.($advance->employee->first_name ?? '').' '.($advance->employee->last_name ?? ''), number_format($advance->amount, 2, ',', ' ').' €')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->url(AdvancePaymentResource::getUrl('edit', ['record' => $advance]));
        }

        // 2. Bulletins validés (non payés)
        $payslips = Payslip::with('employee')
            ->where('status', PayslipStatus::VALIDATED)
            ->get();

        foreach ($payslips as $payslip) {
            $details[] = Detail::make('Paie '.$payslip->period.' - '.($payslip->employee->first_name ?? '').' '.($payslip->employee->last_name ?? ''), number_format($payslip->net_paid, 2, ',', ' ').' €')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->url(PayslipResource::getUrl('edit', ['record' => $payslip]));
        }

        return array_slice($details, 0, 10); // Limiter à 10 éléments
    }
}
