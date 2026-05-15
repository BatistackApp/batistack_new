<?php

namespace App\Console\Commands\Chantiers;

use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use App\Services\Chantiers\ChantierComplianceService;
use Illuminate\Console\Command;

class CheckChantierComplianceCommand extends Command
{
    protected $signature = 'chantier:check-compliance';

    protected $description = 'Vérifie quotidiennement la validité des habilitations pour les chantiers actifs';

    public function handle(ChantierComplianceService $complianceService): int
    {
        $this->info('Début du scan de conformité sécurité...');

        $activeChantiers = Chantier::where('status', ChantierStatus::IN_PROGRESS)->get();
        $issueCount = 0;

        foreach ($activeChantiers as $chantier) {
            $check = $complianceService->checkTeamCompliance($chantier);

            if (! $check['is_compliant']) {
                $issueCount++;
                $this->warn("⚠️ Non-conformité détectée sur : {$chantier->reference} ({$chantier->name})");

                // On peut ici logger les détails ou notifier le manager si ce n'est pas déjà fait par un Job
                foreach ($check['messages'] as $message) {
                    $this->line("   - {$message}");
                }
            }
        }

        $this->info("Scan terminé. {$issueCount} chantier(s) présentent des risques de conformité.");

        return Command::SUCCESS;
    }
}
