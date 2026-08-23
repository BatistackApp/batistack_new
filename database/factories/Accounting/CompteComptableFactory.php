<?php

namespace Database\Factories\Accounting;

use App\Models\Accounting\CompteComptable;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompteComptableFactory extends Factory
{
    protected $model = CompteComptable::class;

    private array $classePrefixes = [
        1 => ['10', '11', '12', '13', '15'],
        2 => ['20', '21', '22', '23', '26', '27', '28', '29'],
        3 => ['31', '32', '34'],
        4 => ['40', '41', '42', '43', '44', '45'],
        5 => ['51', '52', '53', '54', '58'],
        6 => ['60', '61', '62', '63', '64', '65', '66', '67', '68', '69'],
        7 => ['70', '71', '72', '73', '74', '75', '76', '77'],
        8 => ['88', '89'],
    ];

    public function definition(): array
    {
        $classe = $this->faker->randomElement(array_keys($this->classePrefixes));
        $prefix = $this->faker->randomElement($this->classePrefixes[$classe]);
        $suffix = str_pad((string) $this->faker->numberBetween(0, 9999), 6 - strlen($prefix), '0', STR_PAD_LEFT);

        return [
            'numero' => $prefix . $suffix,
            'libelle' => $this->faker->words(3, true),
            'classe' => $classe,
            'is_balance' => $this->faker->boolean(30),
            'parent_id' => null,
        ];
    }

    public function classe1(): static
    {
        $prefix = $this->faker->randomElement($this->classePrefixes[1]);
        $suffix = str_pad((string) $this->faker->numberBetween(0, 9999), 6 - strlen($prefix), '0', STR_PAD_LEFT);

        return $this->state(fn () => ['classe' => 1, 'numero' => $prefix . $suffix]);
    }

    public function classe3(): static
    {
        return $this->state(fn () => ['classe' => 3, 'numero' => '31' . str_pad((string) $this->faker->numberBetween(0, 999), 4, '0')]);
    }

    public function classe4(): static
    {
        return $this->state(fn () => ['classe' => 4, 'numero' => '411' . str_pad((string) $this->faker->numberBetween(0, 999), 3, '0')]);
    }

    public function classe5(): static
    {
        $prefix = $this->faker->randomElement($this->classePrefixes[5]);
        $suffix = str_pad((string) $this->faker->numberBetween(0, 9999), 6 - strlen($prefix), '0', STR_PAD_LEFT);

        return $this->state(fn () => ['classe' => 5, 'numero' => $prefix . $suffix]);
    }

    public function classe6(): static
    {
        return $this->state(fn () => ['classe' => 6, 'numero' => '607' . str_pad((string) $this->faker->numberBetween(0, 999), 3, '0')]);
    }

    public function classe7(): static
    {
        return $this->state(fn () => ['classe' => 7, 'numero' => '707' . str_pad((string) $this->faker->numberBetween(0, 999), 3, '0')]);
    }

    public function balance(): static
    {
        return $this->state(fn () => ['is_balance' => true]);
    }

    public function withParent(): static
    {
        return $this->afterMaking(function (CompteComptable $compte) {
            if (! $compte->parent_id) {
                $parent = CompteComptable::factory()->create(['classe' => $compte->classe]);
                $compte->parent_id = $parent->id;
            }
        });
    }
}
