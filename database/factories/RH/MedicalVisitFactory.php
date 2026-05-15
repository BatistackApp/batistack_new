<?php

namespace Database\Factories\RH;

use App\Enums\RH\MedicalAptitude;
use App\Enums\RH\MedicalVisiteType;
use App\Models\RH\Employee;
use App\Models\RH\MedicalVisit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class MedicalVisitFactory extends Factory
{
    protected $model = MedicalVisit::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(MedicalVisiteType::class),
            'visit_date' => Carbon::now(),
            'next_due_date' => Carbon::now()->addYears(2),
            'aptitude' => $this->faker->randomElement(MedicalAptitude::class),
            'practitioner_name' => $this->faker->name(),
            'notes' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'employee_id' => Employee::factory(),
        ];
    }
}
