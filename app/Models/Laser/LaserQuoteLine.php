<?php

namespace App\Models\Laser;

use App\Observers\Laser\LaserQuoteLineObserver;
use App\Services\Laser\LaserQuoteService;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([LaserQuoteLineObserver::class])]
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
        'density_kg_m3',
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
            'density_kg_m3' => 'decimal:2',
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
        return static::computeSurface((float) $this->length_mm, (float) $this->width_mm);
    }

    public function calculateWeight(): float
    {
        $density = (float) ($this->density_kg_m3 ?? ($this->material?->density_kg_m3 ?? 0));

        return static::computeWeight(
            (float) $this->length_mm,
            (float) $this->width_mm,
            (float) $this->thickness_mm,
            $density,
        );
    }

    public function calculateUnitPrice(): float
    {
        return static::computeUnitPrice(
            $this->calculateWeight(),
            (float) $this->price_per_kg,
            (float) $this->cut_length_mm,
            (float) $this->price_per_meter,
            (float) $this->programming_cost,
        );
    }

    public function calculateDiscount(): float
    {
        return app(LaserQuoteService::class)->applyDiscount((int) $this->quantity);
    }

    public function calculateTotalHt(?float $discountPct = null): float
    {
        return app(LaserQuoteService::class)->calculateLineTotal(
            $this->calculateWeight(),
            (float) $this->price_per_kg,
            (float) $this->cut_length_mm,
            (float) $this->price_per_meter,
            (float) $this->programming_cost,
            (int) $this->quantity,
            $discountPct ?? (float) $this->discount_pct,
        );
    }

    public static function computeSurface(float $lengthMm, float $widthMm): float
    {
        return $lengthMm * $widthMm;
    }

    public static function computeWeight(float $lengthMm, float $widthMm, float $thicknessMm, float $densityKgM3): float
    {
        $surface = static::computeSurface($lengthMm, $widthMm);

        return ($surface / 1_000_000) * $thicknessMm * ($densityKgM3 / 1000);
    }

    public static function computeUnitPrice(float $weightKg, float $pricePerKg, float $cutLengthMm, float $pricePerMeter, float $programmingCost): float
    {
        $prixPoids = $weightKg * $pricePerKg;
        $prixMetre = ($cutLengthMm / 1000) * $pricePerMeter;

        return max($prixPoids, $prixMetre) + $programmingCost;
    }

    public function recalculate(): void
    {
        $discount = $this->calculateDiscount();

        $this->update([
            'density_kg_m3' => $this->material?->density_kg_m3 ?? $this->density_kg_m3,
            'surface_mm2' => $this->calculateSurface(),
            'weight_kg' => $this->calculateWeight(),
            'unit_price_ht' => $this->calculateUnitPrice(),
            'discount_pct' => $discount,
            'total_ht' => $this->calculateTotalHt($discount),
        ]);
    }
}
