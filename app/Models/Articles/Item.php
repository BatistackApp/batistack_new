<?php

namespace App\Models\Articles;

use App\Enums\Articles\ItemType;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Item extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'reference',
        'name',
        'description',
        'type',
        'purchase_price',
        'selling_price',
        'is_active',
        'unit_id',
        'vat_rate_id',
        'min_stock',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }

    /**
     * Pour les Ouvrages : Récupère les composants (Matériels + MO).
     */
    public function components(): HasMany
    {
        return $this->hasMany(ItemComposition::class, 'parent_item_id');
    }

    /**
     * Stock par dépôt.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    protected function casts(): array
    {
        return [
            'type' => ItemType::class,
            'purchase_price' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }
}
