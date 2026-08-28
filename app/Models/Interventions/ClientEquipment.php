<?php

namespace App\Models\Interventions;

use App\Models\Core\Company;
use App\Models\Tiers\ThirdParty;
use Database\Factories\ClientEquipmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientEquipment extends Model
{
    use HasFactory;

    protected $table = 'client_equipments';

    protected static function newFactory()
    {
        return ClientEquipmentFactory::new();
    }

    protected $fillable = [
        'company_id',
        'third_party_id',
        'name',
        'brand',
        'serial_number',
        'installation_date',
    ];

    protected $casts = [
        'installation_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function thirdParty(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class);
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }
}
