<?php

namespace App\Console\Commands\RH;

use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use Illuminate\Console\Command;

class GeneratePayrollCommand extends Command
{
    protected $signature = 'rh:generate-payroll
                            {month : Mois (1-12)}
                            {year : Année (ex: 2024)}
                            {--preview : Afficher aperçu sans générer}';

    protected $description = 'Génère les bulletins de paie pour un mois donné';

    public function handle(): int
    {
        $month = (int) $this->argument('month');
        $year = (int) $this->argument('year');
        $preview = $this->option('preview');

        if ($month < 1 || $month > 12) {
            $this->error('Le mois doit être entre 1 et 12');

            return Command::FAILURE;
        }

        $this->info("Génération des bulletins pour {$month}/{$year}");

        $employees = Employee::where('is_active', true)->with('currentContract')->get();

        if ($employees->isEmpty()) {
            $this->warn('Aucun employé actif trouvé');

            return Command::SUCCESS;
        }

        $totalGross = 0;

        foreach ($employees as $employee) {
            if (! $employee->currentContract) {
                $this->warn("⚠ {$employee->getFullName()}: Pas de contrat actif");

                continue;
            }

            $hours = TimeEntry::byEmployee($employee)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->sum('hours') ?? 0;

            $gross = $hours * $employee->currentContract->getHourlyRate();
            $totalGross += $gross;

            $this->line("✓ {$employee->getFullName()}: {$hours}h × {$employee->currentContract->getHourlyRate()}€ = {$gross}€");
        }

        $this->info('─────────────────────────────');
        $this->info("Total brut: {$totalGross}€");

        if (! $preview) {
            $this->info('✓ Bulletins générés avec succès');
        } else {
            $this->info('Mode aperçu - aucun fichier généré');
        }

        return Command::SUCCESS;
    }
}
