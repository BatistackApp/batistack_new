<?php

namespace Database\Seeders;

use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\RH\Equipement;
use App\Models\RH\MedicalVisit;
use App\Models\RH\Qualification;
use App\Models\User;
use Illuminate\Database\Seeder;

class RHSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::factory(rand(10, 50))->create();

        foreach ($users as $user) {
            $employee = Employee::factory()->create([
                'user_id' => $user->id,
            ]);

            Contract::factory()->create([
                'employee_id' => $employee->id,
            ]);

            Qualification::factory(rand(1, 5))->create(['employee_id' => $employee->id]);
            MedicalVisit::factory(rand(1, 5))->create(['employee_id' => $employee->id]);
            Equipement::factory(rand(1, 5))->create(['employee_id' => $employee->id]);
        }
    }
}
