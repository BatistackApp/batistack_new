<?php

namespace Database\Factories\RH;

use App\Enums\RH\EquipementType;
use App\Models\RH\Employee;
use App\Models\RH\Equipement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class EquipementFactory extends Factory
{
    protected $model = Equipement::class;

    public function definition(): array
    {
        $assigned_at = $this->faker->dateTimeBetween('-3 years', 'now');
        $type = $this->faker->randomElement(EquipementType::class);

        $dataInfos = match ($type) {
            EquipementType::PPE => [
                'elements' => ['casque', 'harnais', 'gants'],
                'durability_in_months' => 12,
            ],
            EquipementType::TOOL => [
                'elements' => ['Perceuse', 'Visseuses', 'Boulonneuse', 'Scie Metal'],
                'durability_in_months' => 6,
            ],
            EquipementType::VEHICLE => [
                'elements' => ['Fonction', 'Service'],
                'durability_in_months' => 999,
            ],
            EquipementType::IT => [
                'elements' => ['PC', 'Mobile'],
                'durability_in_months' => 36,
            ],
        };

        return [
            'type' => $this->faker->randomElement(EquipementType::class),
            'label' => $this->faker->randomElement($dataInfos['elements']),
            'brand' => $this->faker->word(),
            'model_name' => $this->faker->name(),
            'serial_number' => $this->faker->word(),
            'assigned_at' => $assigned_at,
            'expires_at' => (clone $assigned_at)->modify('+'.$dataInfos['durability_in_months'].' months'),
            'last_check_at' => Carbon::now(),
            'notes' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'employee_id' => Employee::factory(),
        ];
    }
}
