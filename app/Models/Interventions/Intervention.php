<?php

namespace App\Models\Interventions;

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Chantiers\Chantier;
use App\Models\Core\Company;
use App\Models\Core\Signature;
use App\Models\Tiers\ThirdParty;
use App\Observers\Interventions\InterventionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([InterventionObserver::class])]
class Intervention extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

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
        'client_equipment_id',
        'maintenance_contract_id',
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

    public function signatures(): MorphMany
    {
        return $this->morphMany(Signature::class, 'signable');
    }

    public function clientEquipment(): BelongsTo
    {
        return $this->belongsTo(ClientEquipment::class);
    }

    public function maintenanceContract(): BelongsTo
    {
        return $this->belongsTo(MaintenanceContract::class);
    }
}
