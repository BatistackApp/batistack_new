<?php

namespace App\Filament\Salarie\Widgets;

use App\Enums\RH\TimeEntryStatus;
use App\Models\RH\TimeEntry;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class TimeEntryRecapWidget extends Widget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.salarie.widgets.time-entry-recap-widget';

    public string $activeFilter = 'month';

    public function getFilterOptions(): array
    {
        return [
            'week' => 'Cette semaine',
            'month' => 'Ce mois-ci',
            'year' => 'Cette année',
        ];
    }

    public function setFilter(string $filter): void
    {
        $this->activeFilter = $filter;
    }

    public function getTimeEntries()
    {
        $employee = Auth::user()?->salarie;

        if (! $employee) {
            return collect();
        }

        $query = TimeEntry::where('employee_id', $employee->id)
            ->with('chantier')
            ->orderBy('date', 'desc');

        $query = match ($this->activeFilter) {
            'week' => $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]),
            'year' => $query->whereYear('date', now()->year),
            default => $query->whereYear('date', now()->year)->whereMonth('date', now()->month),
        };

        return $query->take(10)->get();
    }

    public function getSummary(): array
    {
        $employee = Auth::user()?->salarie;

        if (! $employee) {
            return ['total_hours' => 0, 'approved_hours' => 0, 'pending_count' => 0, 'entry_count' => 0];
        }

        $query = TimeEntry::where('employee_id', $employee->id);

        $query = match ($this->activeFilter) {
            'week' => $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]),
            'year' => $query->whereYear('date', now()->year),
            default => $query->whereYear('date', now()->year)->whereMonth('date', now()->month),
        };

        $entries = $query->get();

        return [
            'total_hours' => round($entries->sum('hours'), 1),
            'approved_hours' => round($entries->where('status', TimeEntryStatus::APPROVED)->sum('hours'), 1),
            'pending_count' => $entries->whereIn('status', [TimeEntryStatus::DRAFT, TimeEntryStatus::SUBMITTED])->count(),
            'entry_count' => $entries->count(),
        ];
    }
}
