<?php

namespace App\Models\Core;

use App\Observers\Core\SettingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([SettingObserver::class])]
class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'group', 'type'];

    /**
     * Scope: Récupérer les settings par groupe
     */
    public function scopeByGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    /**
     * Scope: Récupérer les settings par type
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Récupérer un setting par clé
     */
    public function scopeByKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    /**
     * Scope: Récupérer seulement les settings de groupe 'general'
     */
    public function scopeGeneral(Builder $query): Builder
    {
        return $query->byGroup('general');
    }

    /**
     * Scope: Récupérer seulement les settings de groupe 'billing'
     */
    public function scopeBilling(Builder $query): Builder
    {
        return $query->byGroup('billing');
    }

    /**
     * Scope: Récupérer seulement les settings de groupe 'tax'
     */
    public function scopeTax(Builder $query): Builder
    {
        return $query->byGroup('tax');
    }

    /**
     * Scope: Récupérer seulement les settings critiques
     */
    public function scopeCritical(Builder $query): Builder
    {
        return $query->whereIn('key', [
            'app_name',
            'app_email',
            'company_siren',
            'default_vat_rate',
        ]);
    }

    /**
     * Statique: Récupérer une valeur par clé
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Statique: Définir une valeur par clé
     */
    public static function setValue(string $key, mixed $value, string $group = 'general', string $type = 'string'): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'type' => $type]
        );
    }

    /**
     * Statique: Vérifier si une clé existe
     */
    public static function keyExists(string $key): bool
    {
        return static::where('key', $key)->exists();
    }
}
