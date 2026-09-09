<?php

namespace App\Models\Laser;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaserQuoteLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'laser_quote_id',
        'material_id',
        'description',
        'length_mm',
        'width_mm',
        'thickness_mm',
        'quantity',
        'surface_mm2',
        'cut_length_mm',
        'weight_kg',
        'price_per_kg',
        'price_per_meter',
        'programming_cost',
        'discount_pct',
        'unit_price_ht',
        'total_ht',
    ];

    protected function casts(): array
    {
        return [
            'length_mm' => 'decimal:2',
            'width_mm' => 'decimal:2',
            'thickness_mm' => 'decimal:2',
            'quantity' => 'integer',
            'surface_mm2' => 'decimal:4',
            'cut_length_mm' => 'decimal:2',
            'weight_kg' => 'decimal:4',
            'price_per_kg' => 'decimal:4',
            'price_per_meter' => 'decimal:4',
            'programming_cost' => 'decimal:2',
            'discount_pct' => 'decimal:2',
            'unit_price_ht' => 'decimal:4',
            'total_ht' => 'decimal:2',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(LaserQuote::class, 'laser_quote_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(LaserMaterial::class, 'material_id');
    }

    public function calculateSurface(): float
    {
        return (float) $this->length_mm * (float) $this->width_mm;
    }

    public function calculateWeight(): float
    {
        $surface = $this->calculateSurface();
        $thickness = (float) $this->thickness_mm;
        $density = $this->material ? (float) $this->material->density_kg_m3 : 0;

        return ($surface / 1_000_000) * $thickness * ($density / 1000);
    }

    public function calculatePrixPoids(): float
    {
        return $this->calculateWeight() * (float) $this->price_per_kg;
    }

    public function calculatePrixMetre(): float
    {
        return ((float) $this->cut_length_mm / 1000) * (float) $this->price_per_meter;
    }

    public function calculateUnitPrice(): float
    {
        return max($this->calculatePrixPoids(), $this->calculatePrixMetre()) + (float) $this->programming_cost;
    }

    public function calculateDiscount(): float
    {
        $qty = (int) $this->quantity;

        if ($qty >= 20) {
            return 15.0;
        }
        if ($qty >= 10) {
            return 10.0;
        }
        if ($qty >= 5) {
            return 5.0;
        }

        return 0.0;
    }

    public function calculateTotalHt(): float
    {
        $unitPrice = $this->calculateUnitPrice();
        $qty = (int) $this->quantity;
        $discount = $this->discount_pct / 100;

        return $unitPrice * $qty * (1 - $discount);
    }

    public function recalculate(): void
    {
        $this->update([
            'surface_mm2' => $this->calculateSurface(),
            'weight_kg' => $this->calculateWeight(),
            'unit_price_ht' => $this->calculateUnitPrice(),
            'discount_pct' => $this->calculateDiscount(),
            'total_ht' => $this->calculateTotalHt(),
        ]);
    }
}
