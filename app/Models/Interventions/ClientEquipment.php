<?php

namespace App\Models\Interventions;

use Illuminate\Database\Eloquent\Model;

class ClientEquipment extends Model
{
    protected $table = 'client_equipments';

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

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Core\Company::class);
    }

    public function thirdParty(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Tiers\ThirdParty::class);
    }

    public function interventions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Intervention::class);
    }
}
