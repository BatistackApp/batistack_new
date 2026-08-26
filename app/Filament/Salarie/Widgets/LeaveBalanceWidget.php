<?php

namespace App\Filament\Salarie\Widgets;

use App\Enums\RH\AbsenceType;
use App\Models\RH\Abscence;
use App\Services\RH\LeaveBalanceService;
use Carbon\CarbonPeriod;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class LeaveBalanceWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $employee = Auth::user()?->salarie;

        if (! $employee) {
            return [];
        }

        $service = app(LeaveBalanceService::class);

        $cpBalance = $service->getBalance($employee, AbsenceType::PAID_LEAVE);
        $cpAcquired = $service->getAcquiredRights($employee, AbsenceType::PAID_LEAVE);
        $cpConsumed = $service->getConsumedDays($employee, AbsenceType::PAID_LEAVE);

        $rttBalance = $service->getBalance($employee, AbsenceType::RTT);
        $rttAcquired = $service->getAcquiredRights($employee, AbsenceType::RTT);
        $rttConsumed = $service->getConsumedDays($employee, AbsenceType::RTT);

        $sickDays = Abscence::where('employee_id', $employee->id)
            ->where('type', AbsenceType::SICK_LEAVE)
            ->whereYear('start_date', now()->year)
            ->get()
            ->sum(function (Abscence $absence) {
                $days = 0;
                $period = CarbonPeriod::create($absence->start_date, $absence->end_date);
                foreach ($period as $date) {
                    if (! $date->isWeekend()) {
                        $days++;
                    }
                }

                return $days;
            });

        return [
            Stat::make('Congés Payés', $cpBalance.' j')
                ->description("Acquis : {$cpAcquired} j / Pris : {$cpConsumed} j")
                ->descriptionIcon('heroicon-m-calendar-check')
                ->color($cpBalance <= 2 ? 'danger' : 'success'),

            Stat::make('RTT', $rttBalance.' j')
                ->description("Acquis : {$rttAcquired} j / Pris : {$rttConsumed} j")
                ->descriptionIcon('heroicon-m-clock')
                ->color($rttBalance <= 1 ? 'danger' : 'info'),

            Stat::make('Arrêts Maladie', $sickDays.' j')
                ->description('Cette année')
                ->descriptionIcon('heroicon-m-heart')
                ->color($sickDays > 0 ? 'warning' : 'success'),

            Stat::make('Solde total', ($cpBalance + $rttBalance).' j')
                ->description('Congés + RTT restants')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
        ];
    }
}
