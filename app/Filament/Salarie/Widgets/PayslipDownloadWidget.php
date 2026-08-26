<?php

namespace App\Filament\Salarie\Widgets;

use App\Enums\Paie\PayslipStatus;
use App\Models\Paie\Payslip;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class PayslipDownloadWidget extends Widget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.salarie.widgets.payslip-download-widget';

    public function getPayslips()
    {
        $employee = Auth::user()?->salarie;

        if (! $employee) {
            return collect();
        }

        return Payslip::where('employee_id', $employee->id)
            ->whereIn('status', [PayslipStatus::VALIDATED, PayslipStatus::PAID])
            ->latest('period')
            ->take(3)
            ->get();
    }

    public function getPayslipsCount(): int
    {
        $employee = Auth::user()?->salarie;

        if (! $employee) {
            return 0;
        }

        return Payslip::where('employee_id', $employee->id)
            ->whereIn('status', [PayslipStatus::VALIDATED, PayslipStatus::PAID])
            ->count();
    }
}
