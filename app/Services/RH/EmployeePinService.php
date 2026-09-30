<?php

namespace App\Services\RH;

use App\Models\RH\Employee;
use App\Notifications\RH\EmployeePinResetNotification;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class EmployeePinService
{
    /**
     * Définit un nouveau code PIN pour le salarié (hashé en bcrypt).
     *
     * @throws InvalidArgumentException si le PIN ne contient pas exactement 4 chiffres
     */
    public function setPin(Employee $employee, string $pin): void
    {
        $this->assertValidPin($pin);

        $employee->update(['pin_hash' => Hash::make($pin)]);
    }

    /**
     * Vérifie un code PIN. Retourne false si aucun PIN n'est configuré.
     */
    public function checkPin(Employee $employee, string $pin): bool
    {
        if (! $employee->pin_hash) {
            return false;
        }

        return Hash::check($pin, $employee->pin_hash);
    }

    /**
     * Génère un code PIN provisoire aléatoire, le hash en base
     * et l'envoie par email au salarié.
     *
     * @return string le PIN en clair (uniquement destiné aux tests)
     */
    public function resetPin(Employee $employee): string
    {
        $pin = $this->generatePin();

        $employee->update(['pin_hash' => Hash::make($pin)]);

        $employee->notify(new EmployeePinResetNotification($pin));

        return $pin;
    }

    /**
     * Génère un code PIN aléatoire de 4 chiffres (0000-9999).
     */
    public function generatePin(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    protected function assertValidPin(string $pin): void
    {
        if (! preg_match('/^[0-9]{4}$/', $pin)) {
            throw new InvalidArgumentException('Le code PIN doit être composé de 4 chiffres.');
        }
    }
}
