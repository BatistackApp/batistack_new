<?php

namespace App\Models\Laser;

use App\Observers\Laser\LaserDeliveryNoteLineObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([LaserDeliveryNoteLineObserver::class])]
class LaserDeliveryNoteLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'laser_delivery_note_id',
        'laser_order_line_id',
        'material_id',
        'description',
        'length_mm',
        'width_mm',
        'thickness_mm',
        'quantity',
        'quantity_delivered',
        'cut_length_mm',
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
            'quantity_delivered' => 'integer',
            'cut_length_mm' => 'decimal:2',
            'weight_kg' => 'decimal:4',
            'density_kg_m3' => 'decimal:2',
        ];
    }

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(LaserDeliveryNote::class, 'laser_delivery_note_id');
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
