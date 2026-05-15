<?php

namespace Database\Factories\RH;

use App\Models\RH\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'registration_number' => 'MAT-'.$this->faker->unique()->numberBetween(1000, 9999),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'birth_date' => $this->faker->date('Y-m-d', '-18 years'),
            'social_security_number' => $this->faker->numerify('1##########'),
            'is_active' => true,
            'user_id' => User::factory(),
            'uuid' => $this->faker->uuid(),
            'address' => $this->faker->streetAddress(),
            'postal_code' => $this->faker->postcode(),
            'city' => $this->faker->city(),
        ];
    }
}
