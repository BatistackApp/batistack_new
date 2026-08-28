<?php

namespace App\Filament\Salarie\Widgets;

use App\Models\RH\Abscence;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class UpcomingAbsencesWidget extends Widget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.salarie.widgets.upcoming-absences-widget';

    public function getAbsences()
    {
        $employee = Auth::user()?->salarie;

        if (! $employee) {
            return collect();
        }

        return Abscence::where('employee_id', $employee->id)
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->take(5)
            ->get();
    }

    public function getAbsencesCount(): int
    {
        $employee = Auth::user()?->salarie;

        if (! $employee) {
            return 0;
        }

        return Abscence::where('employee_id', $employee->id)
            ->where('start_date', '>=', now())
            ->count();
    }
}
