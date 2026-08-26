<?php

namespace Database\Seeders;

use App\Enums\Chantiers\ChantierReserveStatus;
use App\Enums\Chantiers\ChantierStatus;
use App\Enums\Chantiers\ReserveSeverity;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierLog;
use App\Models\Chantiers\ChantierPhase;
use App\Models\Chantiers\ChantierReserve;
use App\Models\Chantiers\ChantierTask;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Seeder;

class ChantierSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::all();
        $clients = ThirdParty::where('type', 'client')->get();
        $suppliers = ThirdParty::where('type', 'supplier')->get();

        $statuses = ChantierStatus::cases();
        $phasesLabels = [
            'Installation de chantier',
            'Terrassement & Fondations',
            'Gros OEuvre / Maçonnerie',
            'Charpente & Couverture',
            'Menuiseries Extérieures',
            'Électricité & Plomberie',
            'Finitions & Peinture',
        ];

        for ($i = 0; $i < 5; $i++) {
            $client = $clients->random();
            $manager = $employees->random();
            $startDate = now()->subMonths(rand(1, 6));

            $chantier = Chantier::create([
                'client_id' => $client->id,
                'manager_id' => $manager->id,
                'reference' => 'CH-'.(2026000 + $i),
                'name' => collect(['Résidence ', 'Pavillon ', 'Réfection ', 'Lotissement ', 'Extension '])->random().$client->name,
                'status' => $statuses[array_rand($statuses)],
                'address' => rand(1, 200).' Rue du Chantier '.($i + 1),
                'zip_code' => '750'.str_pad($i, 2, '0', STR_PAD_LEFT),
                'city' => 'Paris',
                'latitude' => 48.8566 + rand(-50, 50) / 1000,
                'longitude' => 2.3522 + rand(-50, 50) / 1000,
                'budget_hours' => rand(200, 2000),
                'budget_material' => rand(10000, 100000),
                'budget_subcontracting' => rand(5000, 50000),
                'budget_total_ht' => rand(50000, 300000),
                'start_date_preview' => $startDate,
                'end_date_preview' => (clone $startDate)->addMonths(rand(3, 12)),
                'start_date' => $startDate,
            ]);

            // Phases du chantier
            $phaseCount = rand(3, 6);
            for ($p = 0; $p < $phaseCount; $p++) {
                $phase = ChantierPhase::create([
                    'chantier_id' => $chantier->id,
                    'label' => $phasesLabels[$p % count($phasesLabels)],
                    'order' => $p + 1,
                ]);

                // Tâches par phase
                $taskCount = rand(2, 4);
                for ($t = 0; $t < $taskCount; $t++) {
                    ChantierTask::create([
                        'chantier_phase_id' => $phase->id,
                        'label' => 'Tâche '.($t + 1).' - '.$phase->label,
                        'description' => 'Description détaillée de la tâche '.($t + 1),
                        'order' => $t + 1,
                        'estimated_hours' => rand(4, 80),
                        'progress_percentage' => rand(0, 100),
                        'is_completed' => rand(0, 100) > 70,
                    ]);
                }
            }

            // Réserves
            $reserveCount = rand(0, 3);
            for ($r = 0; $r < $reserveCount; $r++) {
                ChantierReserve::create([
                    'chantier_id' => $chantier->id,
                    'reference' => 'RS-'.now()->year.'-'.str_pad($i * 10 + $r + 1, 3, '0', STR_PAD_LEFT),
                    'title' => 'Réserve '.($r + 1).' - '.$chantier->name,
                    'description' => 'Problème identifié sur le chantier nécessitant une intervention.',
                    'severity' => collect(ReserveSeverity::cases())->random(),
                    'status' => collect(ChantierReserveStatus::cases())->random(),
                    'due_date' => now()->addDays(rand(7, 60)),
                ]);
            }

            // Logs d'activité
            $logCount = rand(3, 8);
            for ($l = 0; $l < $logCount; $l++) {
                ChantierLog::create([
                    'chantier_id' => $chantier->id,
                    'user_id' => $manager->user_id,
                    'date' => now()->subDays(rand(0, 30)),
                    'content' => collect([
                        'Début des travaux de fondation',
                        'Livraison matériel prévue demain',
                        'Réunion de coordination planifiée',
                        'Pointage des heures effectué',
                        'Phase '.$phasesLabels[array_rand($phasesLabels)].' terminée',
                        'Réserve signalée et documentée',
                    ])->random(),
                ]);
            }
        }
    }
}
