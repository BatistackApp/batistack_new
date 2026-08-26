<?php

namespace App\Filament\Salarie\Widgets;

use App\Enums\Paie\PayslipStatus;
use App\Enums\RH\TimeEntryStatus;
use App\Models\Paie\Payslip;
use App\Models\RH\Abscence;
use App\Models\RH\Qualification;
use App\Models\RH\TimeEntry;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class ActivityFeedWidget extends Widget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.salarie.widgets.activity-feed-widget';

    public array $activities = [];

    public function mount(): void
    {
        $this->loadActivities();
    }

    public function loadActivities(): void
    {
        $employee = Auth::user()?->salarie;

        if (! $employee) {
            $this->activities = [];

            return;
        }

        $activities = collect();

        // Derniers pointages
        TimeEntry::where('employee_id', $employee->id)
            ->with('chantier')
            ->latest('updated_at')
            ->limit(3)
            ->get()
            ->each(function (TimeEntry $entry) use ($activities) {
                $activities->push([
                    'icon' => 'heroicon-o-clock',
                    'color' => match ($entry->status) {
                        TimeEntryStatus::APPROVED => 'success',
                        TimeEntryStatus::SUBMITTED => 'warning',
                        TimeEntryStatus::LOCKED => 'danger',
                        default => 'gray',
                    },
                    'label' => 'Pointage '.$entry->status->getLabel(),
                    'description' => $entry->hours.'h le '.$entry->date->format('d/m/Y').' — '.($entry->chantier?->name ?? 'N/A'),
                    'date' => $entry->updated_at,
                ]);
            });

        // Dernières absences
        Abscence::where('employee_id', $employee->id)
            ->latest('created_at')
            ->limit(3)
            ->get()
            ->each(function (Abscence $absence) use ($activities) {
                $activities->push([
                    'icon' => 'heroicon-o-calendar-days',
                    'color' => match ($absence->type->value) {
                        'paid_leave' => 'success',
                        'rtt' => 'info',
                        'sick_leave' => 'warning',
                        default => 'gray',
                    },
                    'label' => $absence->type->getLabel(),
                    'description' => $absence->start_date->format('d/m/Y').' → '.$absence->end_date->format('d/m/Y'),
                    'date' => $absence->created_at,
                ]);
            });

        // Derniers bulletins
        Payslip::where('employee_id', $employee->id)
            ->whereIn('status', [PayslipStatus::VALIDATED, PayslipStatus::PAID])
            ->latest('updated_at')
            ->limit(2)
            ->get()
            ->each(function (Payslip $payslip) use ($activities) {
                $activities->push([
                    'icon' => 'heroicon-o-banknotes',
                    'color' => match ($payslip->status) {
                        PayslipStatus::PAID => 'success',
                        PayslipStatus::VALIDATED => 'info',
                        default => 'gray',
                    },
                    'label' => 'Bulletin '.$payslip->status->getLabel(),
                    'description' => 'Période : '.$payslip->period,
                    'date' => $payslip->updated_at,
                ]);
            });

        // Dernières qualifications
        Qualification::where('employee_id', $employee->id)
            ->latest('obtained_at')
            ->limit(2)
            ->get()
            ->each(function (Qualification $qualification) use ($activities) {
                $status = $qualification->isExpired() ? 'Expirée' : ($qualification->isExpiringSoon() ? 'Expire bientôt' : 'Valide');

                $activities->push([
                    'icon' => 'heroicon-o-academic-cap',
                    'color' => match (true) {
                        $qualification->isExpired() => 'danger',
                        $qualification->isExpiringSoon() => 'warning',
                        default => 'success',
                    },
                    'label' => 'Qualification : '.$qualification->label->value,
                    'description' => $status.' — obtention le '.$qualification->obtained_at->format('d/m/Y'),
                    'date' => $qualification->obtained_at,
                ]);
            });

        $this->activities = $activities
            ->sortByDesc('date')
            ->take(10)
            ->values()
            ->toArray();
    }
}
