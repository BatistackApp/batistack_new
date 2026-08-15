<?php

namespace Database\Factories\Chantiers;

use App\Enums\Chantiers\ChantierReserveStatus;
use App\Enums\Chantiers\ReserveSeverity;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierReserve;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ChantierReserveFactory extends Factory
{
    protected $model = ChantierReserve::class;

    public function definition(): array
    {
        return [
            'chantier_id' => Chantier::factory(),
            'reference' => 'RS-'.now()->year.'-'.Str::upper(Str::random(5)),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'severity' => $this->faker->randomElement(ReserveSeverity::cases()),
            'status' => ChantierReserveStatus::OPEN,
            'due_date' => $this->faker->dateTimeBetween('now', '+2 months'),
        ];
    }
}
