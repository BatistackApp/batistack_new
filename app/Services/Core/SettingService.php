<?php

namespace App\Services\Core;

use App\Models\Core\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    /**
     * Récupère une valeur de paramètre par sa clé.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::remember("core_setting_{$key}", 3600, function () use ($key) {
            return Setting::where('key', $key)->first()?->value;
        });

        return $value ?? $default;
    }

    /**
     * Met à jour ou crée un paramètre.
     */
    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget("core_setting_{$key}");
    }
}
