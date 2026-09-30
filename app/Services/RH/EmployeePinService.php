<?php

namespace App\Services\RH;

use App\Exceptions\RH\EmployeePinException;
use App\Models\RH\Employee;
use App\Notifications\RH\EmployeePinResetNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;
use Throwable;

class EmployeePinService
{
    /**
     * Nombre maximal de tentatives de code PIN avant verrouillage temporaire.
     */
    public const MAX_PIN_ATTEMPTS = 5;

    /**
     * Durée du verrouillage (en secondes) déclenchée par la dernière tentative échouée.
     */
    public const PIN_ATTEMPT_DECAY_SECONDS = 300;

    /**
     * Définit un nouveau code PIN pour le salarié (hashé en bcrypt).
     *
     * @throws InvalidArgumentException si le PIN ne contient pas exactement 4 chiffres
     */
    public function setPin(Employee $employee, string $pin): void
    {
        $employee->update(['pin_hash' => $this->hashPin($pin)]);
    }

    /**
     * Valide le format d'un code PIN et retourne son hash bcrypt.
     *
     * @throws InvalidArgumentException si le PIN ne contient pas exactement 4 chiffres
     */
    public function hashPin(string $pin): string
    {
        $this->assertValidPin($pin);

        return Hash::make($pin);
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
     * Si l'envoi échoue, l'ancien code PIN est restauré afin que le salarié
     * ne se retrouve jamais avec un code qu'il ignore.
     *
     * @return string le PIN en clair (uniquement destiné aux tests)
     *
     * @throws AuthorizationException si l'utilisateur connecté n'a pas le droit de réinitialiser
     * @throws EmployeePinException si le salarié n'a pas d'adresse email ou si l'envoi échoue
     */
    public function resetPin(Employee $employee): string
    {
        $this->assertCanResetPin($employee);
        $this->assertHasNotificationAddress($employee);

        $pin = $this->generatePin();
        $previousHash = $employee->pin_hash;

        $employee->update(['pin_hash' => Hash::make($pin)]);

        try {
            $employee->notify(new EmployeePinResetNotification($pin));
        } catch (Throwable $exception) {
            $employee->update(['pin_hash' => $previousHash]);

            report($exception);

            throw new EmployeePinException(
                'L\'envoi de l\'email a échoué : le code PIN précédent a été conservé. Vérifiez la configuration email puis réessayez.'
            );
        }

        return $pin;
    }

    /**
     * Construit la clé de rate limiting d'un contexte de vérification de PIN.
     * Chaque couple (salarié, contexte) dispose de son propre compteur.
     */
    public function pinAttemptsKey(Employee $employee, string $context): string
    {
        return sprintf('pin-attempts:%s:%s', $context, $employee->getKey());
    }

    /**
     * Indique si le contexte est verrouillé faute à trop de tentatives échouées.
     */
    public function isPinAttemptLocked(string $key): bool
    {
        return RateLimiter::tooManyAttempts($key, self::MAX_PIN_ATTEMPTS);
    }

    /**
     * Nombre de secondes restantes avant de pouvoir réessayer.
     */
    public function pinAttemptRemainingSeconds(string $key): int
    {
        return RateLimiter::availableIn($key);
    }

    /**
     * Consigne une tentative de code PIN échouée.
     */
    public function registerFailedPinAttempt(string $key): void
    {
        RateLimiter::hit($key, self::PIN_ATTEMPT_DECAY_SECONDS);
    }

    /**
     * Remet à zéro le compteur de tentatives (après une réussite).
     */
    public function clearPinAttempts(string $key): void
    {
        RateLimiter::clear($key);
    }

    /**
     * Génère un code PIN aléatoire de 4 chiffres (0000-9999).
     */
    public function generatePin(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Un utilisateur authentifié doit disposer du droit de mise à jour du salarié
     * (permission Update:Employee via la policy). Les appels système (console,
     * jobs) ne sont pas concernés car ils n'ont aucun utilisateur de session.
     */
    protected function assertCanResetPin(Employee $employee): void
    {
        $user = auth()->user();

        if ($user !== null && ! $user->can('update', $employee)) {
            throw new AuthorizationException('Vous n\'êtes pas autorisé à réinitialiser le code PIN de ce salarié.');
        }
    }

    /**
     * Refuse la réinitialisation tant que le salarié n'a pas d'adresse email
     * exploitable, afin de ne jamais modifier un secret sans pouvoir le transmettre.
     */
    protected function assertHasNotificationAddress(Employee $employee): void
    {
        if (blank($employee->routeNotificationFor('mail'))) {
            throw new EmployeePinException('Le salarié ne possède aucune adresse email : la réinitialisation du code PIN est impossible.');
        }
    }

    protected function assertValidPin(string $pin): void
    {
        if (! preg_match('/^[0-9]{4}$/', $pin)) {
            throw new InvalidArgumentException('Le code PIN doit être composé de 4 chiffres.');
        }
    }
}
