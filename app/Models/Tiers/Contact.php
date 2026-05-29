<?php

namespace App\Models\Tiers;

use App\Models\User;
use App\Observers\Tiers\ContactObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

#[ObservedBy([ContactObserver::class])]
class Contact extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'third_party_id',
        'first_name',
        'last_name',
        'job_title',
        'email',
        'phone',
        'mobile',
        'is_primary',
        'is_active',
        'metadata',
        'user_id',
    ];

    public function thirdParty(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope: Récupérer seulement les contacts actifs
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Récupérer seulement les contacts inactifs
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope: Récupérer le contact principal
     */
    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope: Récupérer les contacts secondaires
     */
    public function scopeSecondary(Builder $query): Builder
    {
        return $query->where('is_primary', false);
    }

    /**
     * Scope: Rechercher par nom
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('first_name', 'like', "%{$term}%")
            ->orWhere('last_name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%");
    }

    /**
     * Scope: Rechercher par email
     */
    public function scopeByEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    /**
     * Scope: Rechercher par fonction
     */
    public function scopeByJobTitle(Builder $query, string $jobTitle): Builder
    {
        return $query->where('job_title', 'like', "%{$jobTitle}%");
    }

    /**
     * Scope: Récupérer les contacts avec email
     */
    public function scopeWithEmail(Builder $query): Builder
    {
        return $query->whereNotNull('email')->where('email', '!=', '');
    }

    /**
     * Scope: Récupérer les contacts avec téléphone
     */
    public function scopeWithPhone(Builder $query): Builder
    {
        return $query->whereNotNull('phone')->where('phone', '!=', '');
    }

    /**
     * Scope: Trier par nom
     */
    public function scopeOrderByName(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('last_name', $direction)
            ->orderBy('first_name', $direction);
    }

    /**
     * Scope: Récupérer les contacts liés à un utilisateur
     */
    public function scopeLinkedToUser(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    // ============================================
    // METHODS MÉTIER
    // ============================================

    /**
     * Récupérer le nom complet
     */
    public function getFullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Vérifier si le contact a un email
     */
    public function hasEmail(): bool
    {
        return ! empty($this->email);
    }

    /**
     * Vérifier si le contact a un téléphone
     */
    public function hasPhone(): bool
    {
        return ! empty($this->phone) || ! empty($this->mobile);
    }

    /**
     * Obtenir le numéro de téléphone préféré
     */
    public function getPreferredPhone(): ?string
    {
        return $this->mobile ?? $this->phone;
    }

    /**
     * Statique: Récupérer par email
     */
    public static function byEmail(string $email): ?self
    {
        return static::where('email', $email)->first();
    }
}
