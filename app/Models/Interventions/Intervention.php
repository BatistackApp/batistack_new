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
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'report_template_id',
        'report_data',
        'last_latitude',
        'last_longitude',
        'last_gps_at',
    ];

    protected $casts = [
        'type' => InterventionType::class,
        'status' => InterventionStatus::class,
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'flat_rate_price' => 'decimal:2',
        'report_data' => 'array',
        'last_latitude' => 'float',
        'last_longitude' => 'float',
        'last_gps_at' => 'datetime',
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

    public function reportTemplate(): BelongsTo
    {
        return $this->belongsTo(InterventionReportTemplate::class);
    }

    public function gpsTracks(): HasMany
    {
        return $this->hasMany(InterventionGpsTrack::class);
    }

    public function latestGpsTrack(): HasOne
    {
        return $this->hasOne(InterventionGpsTrack::class)->latestOfMany('recorded_at');
    }

    /**
     * Renvoie le modèle de rapport applicable : celui explicitement lié à
     * l'intervention (indépendamment de son statut actif), sinon le plus
     * récent modèle actif du même type.
     */
    public function applicableReportTemplate(): ?InterventionReportTemplate
    {
        if ($this->report_template_id) {
            return $this->reportTemplate()->first();
        }

        return InterventionReportTemplate::query()
            ->where('intervention_type', $this->type)
            ->where('is_active', true)
            ->latest('id')
            ->first();
    }
}
