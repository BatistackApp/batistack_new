<?php

namespace App\Models\Interventions;

use App\Enums\Interventions\MaintenanceContractFrequency;
use App\Enums\Interventions\MaintenanceContractStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Core\Company;
use App\Models\Core\Signature;
use App\Models\Tiers\ThirdParty;
use App\Observers\Interventions\MaintenanceContractObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([MaintenanceContractObserver::class])]
class MaintenanceContract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'reference',
        'third_party_id',
        'client_equipment_id',
        'chantier_id',
        'name',
        'description',
        'frequency',
        'start_date',
        'end_date',
        'next_due_date',
        'last_generated_at',
        'flat_rate_price',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => MaintenanceContractFrequency::class,
            'status' => MaintenanceContractStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'next_due_date' => 'date',
            'last_generated_at' => 'datetime',
            'flat_rate_price' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function thirdParty(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class);
    }

    public function clientEquipment(): BelongsTo
    {
        return $this->belongsTo(ClientEquipment::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(MaintenanceContractReminder::class, 'contract_id');
    }

    public function signatures(): MorphMany
    {
        return $this->morphMany(Signature::class, 'signable');
    }
}
