<?php

namespace App\Filament\Chantier\Widgets;

use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use App\Services\Chantiers\ChantierComplianceService;
use Filament\Widgets\Widget;

class GlobalSafetyHealthWidget extends Widget
{
    protected string $view = 'filament.chantier.widgets.global-safety-health-widget';

    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    public function getSafetyStats(): array
    {
        $activeChantiers = Chantier::where('status', ChantierStatus::IN_PROGRESS)->get();
        $complianceService = app(ChantierComplianceService::class);

        $totalActive = $activeChantiers->count();
        if ($totalActive === 0) {
            return ['rate' => 100, 'issues' => 0];
        }

        $compliantCount = 0;
        $totalIssues = 0;

        foreach ($activeChantiers as $chantier) {
            $check = $complianceService->checkTeamCompliance($chantier);
            if ($check['is_compliant']) {
                $compliantCount++;
            }
            $totalIssues += count($check['messages']);
        }

        return [
            'rate' => round(($compliantCount / $totalActive) * 100),
            'issues' => $totalIssues,
            'total' => $totalActive,
        ];
    }
}
