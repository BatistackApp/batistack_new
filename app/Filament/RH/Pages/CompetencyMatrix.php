<?php

namespace App\Filament\RH\Pages;

use App\Models\RH\Employee;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class CompetencyMatrix extends Page
{
    protected string $view = 'filament.rh.pages.competency-matrix';

    public static function getNavigationIcon(): string|Htmlable|null
    {
        return 'heroicon-o-table-cells';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Gestion';
    }

    public static function getNavigationSort(): ?int
    {
        return 110;
    }

    public static function getNavigationLabel(): string
    {
        return 'Matrice de Polyvalence';
    }

    public function getTitle(): string
    {
        return 'Matrice de Polyvalence & Habilitations';
    }

    public function getEmployeesProperty()
    {
        return Employee::active()->with(['qualifications', 'equipements'])->get();
    }

    public function getCommonQualificationsProperty()
    {
        // Extract unique qualification names from all active employees to use as columns
        return Employee::active()
            ->with('qualifications')
            ->get()
            ->flatMap->qualifications
            ->pluck('label.value')
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    public function getCommonEquipementsProperty()
    {
        // Extract unique equipment names from all active employees to use as columns
        return Employee::active()
            ->with('equipements')
            ->get()
            ->flatMap->equipements
            ->pluck('label')
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }
}
