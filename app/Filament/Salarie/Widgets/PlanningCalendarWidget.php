<?php

namespace App\Filament\Salarie\Widgets;

use App\Models\RH\Abscence;
use App\Models\RH\TimeEntry;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PlanningCalendarWidget extends CalendarWidget
{
    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public function getEvents(array|FetchInfo $info): Collection|array
    {
        $employee = Auth::user()?->salarie;

        if (! $employee) {
            return collect();
        }

        $events = collect();

        $start = is_array($info) ? $info['start'] : $info->start;
        $end = is_array($info) ? $info['end'] : $info->end;

        // Absences of the employee
        Abscence::where('employee_id', $employee->id)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end]);
            })
            ->get()
            ->each(function (Abscence $absence) use ($events) {
                $events->push(
                    CalendarEvent::make()
                        ->title($absence->type->getLabel())
                        ->start($absence->start_date)
                        ->end($absence->end_date)
                        ->backgroundColor($this->mapColor($absence->type->getColor()))
                );
            });

        // Time entries of the employee
        TimeEntry::where('employee_id', $employee->id)
            ->whereBetween('date', [$start, $end])
            ->with('chantier')
            ->get()
            ->each(function (TimeEntry $entry) use ($events) {
                $title = $entry->hours.'h';
                if ($entry->chantier) {
                    $title .= ' — '.$entry->chantier->name;
                }

                $events->push(
                    CalendarEvent::make()
                        ->title($title)
                        ->start($entry->date)
                        ->end($entry->date)
                        ->backgroundColor($this->mapColor($entry->status->getColor()))
                );
            });

        return $events;
    }

    private function mapColor(?string $color): string
    {
        return match ($color) {
            'success' => '#10b981',
            'warning' => '#f59e0b',
            'danger' => '#ef4444',
            'info', 'primary' => '#3b82f6',
            'gray' => '#6b7280',
            default => '#3b82f6',
        };
    }
}
