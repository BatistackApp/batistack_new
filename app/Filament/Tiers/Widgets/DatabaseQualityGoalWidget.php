<?php

namespace App\Filament\Tiers\Widgets;

use App\Models\Tiers\ThirdParty;
use LaBoiteACode\FilamentDashboardWidgets\Data\Goal;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\GoalProgressWidget;

class DatabaseQualityGoalWidget extends GoalProgressWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Qualité de la Base de Données';
    }

    protected function getGoal(): Goal
    {
        $professionalThirdParties = ThirdParty::active()
            ->whereNotNull('siren')
            ->get();

        $totalPro = $professionalThirdParties->count();
        $completeCount = 0;

        foreach ($professionalThirdParties as $tp) {
            // Un profil complet a un SIRET, un numéro de TVA et un email
            if (! empty($tp->siret) && ! empty($tp->vat_number) && ! empty($tp->email)) {
                $completeCount++;
            }
        }

        $target = $totalPro > 0 ? $totalPro : 1;

        $percentage = $totalPro > 0 ? ($completeCount / $totalPro) * 100 : 0;
        $color = $percentage >= 95 ? 'success' : ($percentage >= 80 ? 'warning' : 'danger');

        return Goal::make('Fiches Pro complètes', $completeCount, $target)
            ->color($color);
    }
}
