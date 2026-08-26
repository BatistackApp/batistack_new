<?php

namespace App\Models\Immobilisation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDisposal extends Model
{
    protected $fillable = [
        'fixed_asset_id',
        'disposal_date',
        'sale_price',
        'reason',
        'profit_or_loss',
    ];

    protected function casts(): array
    {
        return [
            'disposal_date' => 'date',
            'sale_price' => 'decimal:2',
            'profit_or_loss' => 'decimal:2',
        ];
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }
}
