<?php

namespace App\Models\Interventions;

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Chantiers\Chantier;
use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use App\Observers\Interventions\InterventionObserver;

#[ObservedBy([InterventionObserver::class])]
class Intervention extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'reference',
        'type',
        'status',
        'third_party_id',
        'chantier_id',
        'description',
        'scheduled_at',
        'completed_at',
        'flat_rate_price',
    ];

    protected $casts = [
        'type' => InterventionType::class,
        'status' => InterventionStatus::class,
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'flat_rate_price' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function thirdParty(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function workers(): HasMany
    {
        return $this->hasMany(InterventionWorker::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(InterventionMaterial::class);
    }

    public function signatures(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\Core\Signature::class, 'signable');
    }
}
