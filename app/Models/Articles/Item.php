<?php

namespace App\Models\Articles;

use App\Enums\Articles\ItemType;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use App\Observers\Articles\BarcodeObserver;
use App\Observers\Articles\ItemObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([ItemObserver::class])]
#[ObservedBy([BarcodeObserver::class])]
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
        'parent_id',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'parent_id');
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
