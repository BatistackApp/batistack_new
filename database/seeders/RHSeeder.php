<?php

namespace Database\Seeders;

use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\RH\Equipement;
use App\Models\RH\MedicalVisit;
use App\Models\RH\Qualification;
use App\Models\RH\TimeEntry;
use App\Models\User;
use Illuminate\Database\Seeder;

class RHSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::factory(12)->create();

        foreach ($users as $user) {
            $employee = Employee::factory()->create([
                'user_id' => $user->id,
            ]);

            Contract::factory()->create([
                'employee_id' => $employee->id,
            ]);

            Qualification::factory(rand(1, 3))->create(['employee_id' => $employee->id]);
            MedicalVisit::factory(rand(1, 2))->create(['employee_id' => $employee->id]);
            Equipement::factory(rand(1, 3))->create(['employee_id' => $employee->id]);

            $statusApproved = TimeEntryStatus::APPROVED;
            $statusDraft = TimeEntryStatus::DRAFT;
            $normal = TimeEntryType::NORMAL;

            $entries = [];
            for ($d = 0; $d < 30; $d++) {
                if (rand(0, 100) > 15) {
                    $date = now()->subDays($d)->format('Y-m-d');
                    $status = rand(0, 100) > 30 ? $statusApproved : $statusDraft;

                    $entries[] = [
                        'employee_id' => $employee->id,
                        'chantier_id' => null,
                        'date' => $date,
                        'hours' => rand(60, 100) / 10,
                        'type' => $normal,
                        'status' => $status,
                        'refusal_reason' => $status === $statusDraft ? 'En attente validation' : null,
                        'approved_by_id' => $status === $statusApproved ? 1 : null,
                        'approved_at' => $status === $statusApproved ? now()->subHours(rand(1, 24)) : null,
                        'is_grand_deplacement' => false,
                        'gd_allowance_amount' => 0,
                        'description' => 'Pointage du '.$date,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if ($entries !== []) {
                TimeEntry::insert($entries);
            }
        }
    }
}
