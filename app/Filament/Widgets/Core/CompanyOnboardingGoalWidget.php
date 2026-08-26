<?php

namespace App\Filament\Widgets\Core;

use App\Models\Core\Company;
use LaBoiteACode\FilamentDashboardWidgets\Data\Goal;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\GoalProgressWidget;

class CompanyOnboardingGoalWidget extends GoalProgressWidget
{
    protected static ?int $sort = 4;

    protected string $view = 'filament.widgets.company-onboarding-goal-widget';

    public function getHeading(): string
    {
        return 'Progression de l\'Onboarding';
    }

    public function getHeadingDescription(): ?string
    {
        return 'Paramètres globaux de l\'application';
    }

    protected function getGoal(): Goal
    {
        $criticalKeys = [
            'legal_name' => 'Nom de l\'entreprise',
            'email' => 'Email',
            'siret' => 'SIRET',
            'vat_number' => 'TVA',
        ];

        $target = count($criticalKeys);

        $company = Company::first();

        $current = 0;
        $missingLabels = [];

        foreach ($criticalKeys as $key => $label) {
            if ($company && ! empty($company->{$key})) {
                $current++;
            } else {
                $missingLabels[] = $label;
            }
        }

        $description = 'Configuration des paramètres critiques de l\'entreprise.';
        if (! empty($missingLabels)) {
            $description .= ' Manquant(s) : '.implode(', ', $missingLabels).'.';
        }

        return Goal::make('Complétion de la configuration', $current, $target)
            ->description($description)
            ->color($current === $target ? 'success' : 'warning')
            ->icon('heroicon-o-check-badge')
            ->url(route('filament.core.pages.manage-company'));
    }
}
