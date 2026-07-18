<?php

namespace App\Models\Immobilisation;

use Illuminate\Database\Eloquent\Model;

class Depreciation extends Model
{
    protected $fillable = [
        'fixed_asset_id',
        'chantier_id',
        'period_date',
        'amount',
        'remaining_vnc',
        'is_passed',
    ];

    protected function casts(): array
    {
        return [
            'period_date' => 'date',
            'amount' => 'decimal:2',
            'remaining_vnc' => 'decimal:2',
            'is_passed' => 'boolean',
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
