<?php

namespace App\Models\Laser;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaserInvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'laser_invoice_id',
        'laser_order_line_id',
        'material_id',
        'description',
        'length_mm',
        'width_mm',
        'thickness_mm',
        'quantity',
        'quantity_invoiced',
        'unit_price_ht',
        'discount_pct',
        'total_ht',
        'weight_kg',
        'density_kg_m3',
    ];

    protected function casts(): array
    {
        return [
            'length_mm' => 'decimal:2',
            'width_mm' => 'decimal:2',
            'thickness_mm' => 'decimal:2',
            'quantity' => 'integer',
            'quantity_invoiced' => 'integer',
            'unit_price_ht' => 'decimal:4',
            'discount_pct' => 'decimal:2',
            'total_ht' => 'decimal:2',
            'weight_kg' => 'decimal:4',
            'density_kg_m3' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(LaserInvoice::class, 'laser_invoice_id');
    }

    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(LaserOrderLine::class, 'laser_order_line_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(LaserMaterial::class, 'material_id');
    }
}
