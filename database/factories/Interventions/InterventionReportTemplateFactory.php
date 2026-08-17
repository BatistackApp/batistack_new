<?php

namespace Database\Factories\Interventions;

use App\Enums\Interventions\InterventionType;
use App\Models\Interventions\InterventionReportTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InterventionReportTemplate>
 */
class InterventionReportTemplateFactory extends Factory
{
    protected $model = InterventionReportTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'intervention_type' => InterventionType::REGIE,
            'schema' => [
                ['type' => 'text_input', 'data' => ['name' => 'constat', 'label' => 'Constat', 'required' => true]],
            ],
            'is_active' => true,
        ];
    }
}
