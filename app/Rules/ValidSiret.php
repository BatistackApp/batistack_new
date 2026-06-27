<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidSiret implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $siret = preg_replace('/\s+/', '', (string) $value);

        if (strlen($siret) !== 14) {
            $fail('Le numéro SIRET doit contenir exactement 14 chiffres.');
            return;
        }

        try {
            $sirenService = app(\App\Services\Core\SirenService::class);
            
            // Validation locale rapide (Luhn)
            if (!$sirenService->isValid($siret)) {
                $fail('Ce numéro SIRET est invalide (clé de contrôle Luhn incorrecte).');
                return;
            }

            // Vérification API
            $info = $sirenService->getInformation($siret);

            // getInformation renvoie null si l'API retourne 404
            if ($info === null) {
                $fail('Ce numéro SIRET est introuvable dans la base de données de l\'INSEE.');
            }
            
        } catch (\Exception $e) {
            // Approche Fail-Open : on accepte la valeur si l'API est indisponible ou non configurée
            \Illuminate\Support\Facades\Log::warning("ValidSiret : Erreur API INSEE (Fail-Open) pour le SIRET {$siret} : " . $e->getMessage());
        }
    }
}
