<?php

namespace App\Models\Immobilisation;

use Illuminate\Database\Eloquent\Model;

class AssetMaintenance extends Model
{
    protected $fillable = [
        'fixed_asset_id',
        'chantier_id',
        'maintenance_date',
        'type',
        'description',
        'cost_ht',
        'provider_name',
        'invoice_ref',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_date' => 'date',
            'cost_ht' => 'decimal:2',
        ];
    }

    public function fixedAsset(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function chantier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Chantiers\Chantier::class);
    }
}
