<?php

namespace Database\Factories\Accounting;

use App\Enums\Accounting\JournalType;
use App\Enums\Accounting\LettrageStatus;
use App\Models\Accounting\CompteComptable;
use App\Models\Accounting\EcritureComptable;
use Illuminate\Database\Eloquent\Factories\Factory;

class EcritureComptableFactory extends Factory
{
    protected $model = EcritureComptable::class;

    public function definition(): array
    {
        $isDebit = $this->faker->boolean();

        return [
            'date_ecriture' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'date_piece' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'journal_type' => $this->faker->randomElement(JournalType::cases()),
            'numero_piece' => strtoupper($this->faker->bothify('??-####')),
            'compte_numero' => fn () => CompteComptable::factory()->create()->numero,
            'libelle' => $this->faker->sentence(3),
            'debit' => $isDebit ? $this->faker->randomFloat(2, 10, 10000) : 0,
            'credit' => $isDebit ? 0 : $this->faker->randomFloat(2, 10, 10000),
            'lettrage' => null,
            'lettrage_status' => LettrageStatus::NON_LETTRÉE,
            'reconcilable_type' => null,
            'reconcilable_id' => null,
            'chantier_id' => null,
        ];
    }

    public function debit(float $amount = null): static
    {
        return $this->state(fn () => [
            'debit' => $amount ?? $this->faker->randomFloat(2, 10, 10000),
            'credit' => 0,
        ]);
    }

    public function credit(float $amount = null): static
    {
        return $this->state(fn () => [
            'debit' => 0,
            'credit' => $amount ?? $this->faker->randomFloat(2, 10, 10000),
        ]);
    }

    public function forChantier(int $chantierId): static
    {
        return $this->state(fn () => ['chantier_id' => $chantierId]);
    }

    public function lettrée(string $lettrageCode = null): static
    {
        return $this->state(fn () => [
            'lettrage' => $lettrageCode ?? strtoupper($this->faker->bothify('??-??')),
            'lettrage_status' => LettrageStatus::LETTRÉE,
        ]);
    }

    public function ofJournal(JournalType $journal): static
    {
        return $this->state(fn () => ['journal_type' => $journal]);
    }
}
