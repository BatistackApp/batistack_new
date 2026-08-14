<?php

namespace App\Models\Vision3D;

use App\Models\Articles\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class BimQuantity extends Model
{
    use HasFactory;

    protected $fillable = [
        'bim_model_id',
        'item_id',
        'element_name',
        'unit',
        'quantity_required',
    ];

    protected $casts = [
        'quantity_required' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::saving(function (BimQuantity $quantity) {
            if ($quantity->quantity_required !== null && (float) $quantity->quantity_required <= 0) {
                throw new InvalidArgumentException('La quantité requise doit être strictement positive.');
            }
        });
    }

    public function bimModel(): BelongsTo
    {
        return $this->belongsTo(BimModel::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
