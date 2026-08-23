<?php

namespace Database\Factories\Paie;

use App\Enums\Paie\DsnSubmissionStatus;
use App\Models\Paie\DsnSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DsnSubmission>
 */
class DsnSubmissionFactory extends Factory
{
    protected $model = DsnSubmission::class;

    public function definition(): array
    {
        return [
            'period' => $this->faker->date('Y-m'),
            'status' => DsnSubmissionStatus::EXPORTED,
            'export_type' => 'csv_expert',
            'exported_at' => now(),
            'exported_file_path' => 'documents/exports/test.csv',
            'payslips_count' => $this->faker->numberBetween(1, 20),
            'total_gross' => $this->faker->randomFloat(2, 1000, 50000),
            'total_net' => $this->faker->randomFloat(2, 800, 35000),
            'total_employer_cost' => $this->faker->randomFloat(2, 1500, 60000),
            'created_by' => User::factory(),
        ];
    }
}
