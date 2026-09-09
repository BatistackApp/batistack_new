<?php

namespace App\Models\Laser;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaserMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'density_kg_m3',
        'price_per_kg',
        'price_per_meter',
        'min_thickness_mm',
        'max_thickness_mm',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'density_kg_m3' => 'decimal:2',
            'price_per_kg' => 'decimal:4',
            'price_per_meter' => 'decimal:4',
            'min_thickness_mm' => 'decimal:2',
            'max_thickness_mm' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function quoteLines(): HasMany
    {
        return $this->hasMany(LaserQuoteLine::class, 'material_id');
    }
}
