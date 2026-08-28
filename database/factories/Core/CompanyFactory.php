<?php

namespace Database\Factories\Core;

use App\Models\Core\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'legal_name' => $this->faker->company(),
            'siret' => $this->faker->numerify('##############'),
            'vat_number' => $this->faker->bothify('FR##########'),
            'share_capital' => $this->faker->randomFloat(2, 1000, 1000000),
            'address' => $this->faker->streetAddress(),
            'zip_code' => $this->faker->postcode(),
            'city' => $this->faker->city(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'website' => $this->faker->url(),
        ];
    }
}
