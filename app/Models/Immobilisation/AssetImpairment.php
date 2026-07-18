<?php

namespace App\Models\Immobilisation;

use Illuminate\Database\Eloquent\Model;

class AssetImpairment extends Model
{
    protected $fillable = [
        'fixed_asset_id',
        'date',
        'amount',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function fixedAsset(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }
}
