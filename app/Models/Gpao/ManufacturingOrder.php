<?php

namespace App\Models\Gpao;

use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Articles\Item;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerOrder;
use App\Models\RH\TimeEntry;
use App\Observers\Gpao\ManufacturingOrderObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[ObservedBy([ManufacturingOrderObserver::class])]
class ManufacturingOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'reference',
        'item_id',
        'chantier_id',
        'customer_order_id',
        'parent_id',
        'quantity_planned',
        'quantity_produced',
        'status',
        'start_date',
        'end_date',
        'total_labor_cost',
        'total_material_cost',
    ];

    protected function casts(): array
    {
        return [
            'status' => ManufacturingStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'quantity_planned' => 'decimal:4',
            'quantity_produced' => 'decimal:4',
            'total_labor_cost' => 'decimal:2',
            'total_material_cost' => 'decimal:2',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($model) => $model->uuid = (string) Str::uuid());
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function customerOrder(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ManufacturingOrder::class, 'parent_id');
    }

    public function qualityChecks(): HasMany
    {
        return $this->hasMany(QualityCheck::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(ManufacturingRequirement::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function getTotalCostAttribute(): float
    {
        return (float) ($this->total_labor_cost + $this->total_material_cost);
    }
}
