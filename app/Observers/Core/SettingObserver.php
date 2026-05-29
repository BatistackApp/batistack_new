<?php

namespace App\Observers\Core;

use App\Models\Core\Setting;
use Exception;
use Illuminate\Support\Facades\Cache;
use Log;

class SettingObserver
{
    /**
     * Paramètres critiques qui ne peuvent pas être supprimés
     */
    protected array $criticalSettings = [
        'app_name',
        'app_email',
        'company_siren',
        'default_vat_rate',
    ];

    /**
     * Valider avant création
     *
     * @throws Exception
     */
    public function creating(Setting $setting): void
    {
        $this->validateValue($setting);
    }

    /**
     * Valider avant mise à jour
     *
     * @throws Exception
     */
    public function updating(Setting $setting): void
    {
        $this->validateValue($setting);
    }

    /**
     * Invalider le cache après création
     */
    public function created(Setting $setting): void
    {
        Cache::forget("core_setting_{$setting->key}");
        Cache::forget('core_settings_all');
        Log::info('Setting created', [
            'key' => $setting->key,
            'group' => $setting->group,
        ]);
    }

    /**
     * Invalider le cache après mise à jour
     */
    public function updated(Setting $setting): void
    {
        Cache::forget("core_setting_{$setting->key}");
        Cache::forget('core_settings_all');
        Log::info('Setting updated', [
            'key' => $setting->key,
            'group' => $setting->group,
        ]);
    }

    /**
     * Empêcher suppression des settings critiques
     *
     * @throws Exception
     */
    public function deleting(Setting $setting): bool
    {
        if (in_array($setting->key, $this->criticalSettings)) {
            Log::warning('Tentative suppression setting critique', [
                'setting_key' => $setting->key,
            ]);
            throw new Exception("Impossible de supprimer le paramètre critique: {$setting->key}");
        }

        return true;
    }

    /**
     * Invalider le cache après suppression
     */
    public function deleted(Setting $setting): void
    {
        Cache::forget("core_setting_{$setting->key}");
        Cache::forget('core_settings_all');
        Log::info('Setting deleted', [
            'key' => $setting->key,
        ]);
    }

    /**
     * Valider la valeur du paramètre
     *
     * @throws Exception
     */
    private function validateValue(Setting $setting): void
    {
        if (empty($setting->key)) {
            throw new Exception('La clé du paramètre est obligatoire');
        }

        // Validation par type
        match ($setting->type ?? 'string') {
            'integer' => $this->validateInteger($setting->value),
            'boolean' => $this->validateBoolean($setting->value),
            'email' => $this->validateEmail($setting->value),
            'url' => $this->validateUrl($setting->value),
            'array' => $this->validateArray($setting->value),
            default => true,
        };
    }

    /**
     * @throws Exception
     */
    private function validateInteger($value): void
    {
        if (! is_numeric($value) && ! is_int($value)) {
            throw new Exception('La valeur doit être un nombre entier');
        }
    }

    /**
     * @throws Exception
     */
    private function validateBoolean($value): void
    {
        if (! in_array($value, ['true', 'false', 1, 0, true, false])) {
            throw new Exception('La valeur doit être un booléen');
        }
    }

    /**
     * @throws Exception
     */
    private function validateEmail($value): void
    {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('L\'email fourni est invalide');
        }
    }

    /**
     * @throws Exception
     */
    private function validateUrl($value): void
    {
        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            throw new Exception('L\'URL fournie est invalide');
        }
    }

    /**
     * @throws Exception
     */
    private function validateArray($value): void
    {
        if (is_string($value)) {
            if (! json_validate($value)) {
                throw new Exception('La valeur doit être un JSON valide');
            }
        } elseif (! is_array($value)) {
            throw new Exception('La valeur doit être un tableau');
        }
    }
}
