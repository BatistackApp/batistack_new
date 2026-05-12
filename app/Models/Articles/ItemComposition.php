<?php

namespace App\Models\Articles;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemComposition extends Model
{
    use HasFactory;

    protected $fillable = [
        'quantity',
        'parent_item_id',
        'child_item_id',
    ];

    public function childItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'child_item_id');
    }
}
