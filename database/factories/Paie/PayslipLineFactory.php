<?php

namespace Database\Factories\Paie;

use App\Models\Paie\PayslipLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayslipLine>
 */
class PayslipLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payslip_id' => \App\Models\Paie\Payslip::factory(),
            'category' => 'SANTE',
            'label' => 'Sécurité Sociale Maladie',
            'base' => 1747.24,
            'employee_rate' => 0.00,
            'employer_rate' => 7.00,
            'employee_amount' => 0.00,
            'employer_amount' => 122.31,
        ];
    }
}
