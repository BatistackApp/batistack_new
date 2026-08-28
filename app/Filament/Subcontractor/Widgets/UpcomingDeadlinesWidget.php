<?php

namespace App\Filament\Subcontractor\Widgets;

use App\Models\Chantiers\ChantierTask;
use App\Models\Tiers\Consultation;
use App\Models\Tiers\ThirdParty;
use LaBoiteACode\FilamentDashboardWidgets\Data\Detail;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\DetailListWidget;

class UpcomingDeadlinesWidget extends DetailListWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Échéances à venir';
    }

    protected function getDetails(): array
    {
        $thirdParty = $this->getSubcontractor();

        if (! $thirdParty) {
            return [];
        }

        $details = [];

        $tasks = ChantierTask::whereHas('allocations', function ($query) use ($thirdParty) {
            $query->where('allocatable_type', ThirdParty::class)
                ->where('allocatable_id', $thirdParty->id);
        })
            ->whereNotNull('end_date')
            ->where('end_date', '>=', now())
            ->where('is_completed', false)
            ->orderBy('end_date', 'asc')
            ->limit(5)
            ->with('phase.chantier')
            ->get();

        foreach ($tasks as $task) {
            $chantierRef = $task->phase?->chantier?->reference ?? '—';
            $days = (int) now()->diffInDays($task->end_date, false);
            $color = $days <= 3 ? 'danger' : ($days <= 7 ? 'warning' : 'gray');

            $details[] = Detail::make(
                "[{$chantierRef}] {$task->label}",
                'Fin prévue le '.$task->end_date->format('d/m/Y')." ({$days}j)"
            )
                ->icon('heroicon-o-clipboard-document-list')
                ->color($color);
        }

        $consultations = Consultation::where('status', 'published')
            ->whereNotNull('deadline')
            ->where('deadline', '>=', now())
            ->orderBy('deadline', 'asc')
            ->limit(3)
            ->get();

        foreach ($consultations as $consultation) {
            $days = (int) now()->diffInDays($consultation->deadline, false);
            $color = $days <= 2 ? 'danger' : ($days <= 5 ? 'warning' : 'gray');

            $details[] = Detail::make(
                $consultation->title,
                'Date limite le '.$consultation->deadline->format('d/m/Y H:i')." ({$days}j)"
            )
                ->icon('heroicon-o-megaphone')
                ->color($color);
        }

        if (empty($details)) {
            return [
                Detail::make('Aucune échéance', 'Aucune échéance à venir')
                    ->icon('heroicon-o-check-circle')
                    ->color('success'),
            ];
        }

        return $details;
    }

    private function getSubcontractor(): ?ThirdParty
    {
        $user = auth()->user();

        if (! $user || ! $user->contact || ! $user->contact->thirdParty) {
            return null;
        }

        return $user->contact->thirdParty;
    }
}
