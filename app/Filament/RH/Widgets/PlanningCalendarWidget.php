<?php

namespace App\Filament\RH\Widgets;

use App\Models\RH\Abscence;
use App\Models\RH\TimeEntry;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\Event;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Support\Collection;

class PlanningCalendarWidget extends CalendarWidget
{
    protected static ?int $sort = 3;

    protected bool $isHeaderCalendar = true; // Use simple true/false if valid, or just let it be

    public function getEvents(array|FetchInfo $fetchInfo): Collection|array
    {
        $events = collect();

        $start = is_array($fetchInfo) ? $fetchInfo['start'] : $fetchInfo->start;
        $end = is_array($fetchInfo) ? $fetchInfo['end'] : $fetchInfo->end;

        // 1. Fetch Absences
        $absences = Abscence::with('employee')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end]);
            })
            ->get();

        foreach ($absences as $absence) {
            $events->push(
                Event::make()
                    ->id('abs_'.$absence->id)
                    ->title($absence->employee->full_name.' - '.$absence->type->getLabel())
                    ->start($absence->start_date)
                    ->end($absence->end_date)
                    // We map enum colors to full hex or valid css colors, or rely on Guava handling Filament colors
                    ->backgroundColor($this->getColorHex($absence->type->getColor()))
            );
        }

        // 2. Fetch TimeEntries (Plannings)
        $timeEntries = TimeEntry::with(['employee', 'chantier'])
            ->whereBetween('date', [$start, $end])
            ->get();

        foreach ($timeEntries as $entry) {
            $title = $entry->employee->full_name.' ('.$entry->hours.'h)';
            if ($entry->chantier) {
                $title .= ' - '.$entry->chantier->name;
            }

            $events->push(
                Event::make()
                    ->id('time_'.$entry->id)
                    ->title($title)
                    ->start($entry->date)
                    ->end($entry->date)
                    ->backgroundColor($this->getColorHex($entry->status->getColor()))
            );
        }

        return $events;
    }

    private function getColorHex(?string $color): string
    {
        return match ($color) {
            'success' => '#10b981', // emerald-500
            'warning' => '#f59e0b', // amber-500
            'danger' => '#ef4444',  // red-500
            'info', 'primary' => '#3b82f6', // blue-500
            'gray' => '#6b7280',    // gray-500
            default => '#3b82f6',
        };
    }
}
