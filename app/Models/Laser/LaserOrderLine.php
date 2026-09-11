<?php

namespace App\Models\Laser;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaserOrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'laser_order_id',
        'material_id',
        'description',
        'length_mm',
        'width_mm',
        'thickness_mm',
        'quantity',
        'delivered_quantity',
        'reserved_quantity',
        'invoiced_quantity',
        'surface_mm2',
        'cut_length_mm',
        'weight_kg',
        'price_per_kg',
        'price_per_meter',
        'programming_cost',
        'discount_pct',
        'unit_price_ht',
        'total_ht',
        'density_kg_m3',
    ];

    protected function casts(): array
    {
        return [
            'length_mm' => 'decimal:2',
            'width_mm' => 'decimal:2',
            'thickness_mm' => 'decimal:2',
            'quantity' => 'integer',
            'delivered_quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'invoiced_quantity' => 'integer',
            'surface_mm2' => 'decimal:4',
            'cut_length_mm' => 'decimal:2',
            'weight_kg' => 'decimal:4',
            'price_per_kg' => 'decimal:4',
            'price_per_meter' => 'decimal:4',
            'programming_cost' => 'decimal:2',
            'discount_pct' => 'decimal:2',
            'unit_price_ht' => 'decimal:4',
            'total_ht' => 'decimal:2',
            'density_kg_m3' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(LaserOrder::class, 'laser_order_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(LaserMaterial::class, 'material_id');
    }

    public function deliveryNoteLines(): HasMany
    {
        return $this->hasMany(LaserDeliveryNoteLine::class, 'laser_order_line_id');
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(LaserInvoiceLine::class, 'laser_order_line_id');
    }

    public function getRemainingQuantityAttribute(): int
    {
        return $this->quantity - $this->delivered_quantity - $this->reserved_quantity;
    }
}
