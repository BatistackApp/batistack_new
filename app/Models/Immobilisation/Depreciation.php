<?php

namespace App\Models\Immobilisation;

use App\Models\Chantiers\Chantier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depreciation extends Model
{
    protected $fillable = [
        'fixed_asset_id',
        'chantier_id',
        'period_date',
        'amount',
        'remaining_vnc',
        'is_passed',
        'grant_reversal_amount',
        'grant_remaining_amount',
    ];

    protected function casts(): array
    {
        return [
            'period_date' => 'date',
            'amount' => 'decimal:2',
            'remaining_vnc' => 'decimal:2',
            'grant_reversal_amount' => 'decimal:2',
            'grant_remaining_amount' => 'decimal:2',
            'is_passed' => 'boolean',
        ];
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }
}
