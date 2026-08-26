<?php

namespace App\Models\Locations;

use App\Models\Tiers\ThirdParty;
use Database\Factories\Locations\SupplierPriceGridFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierPriceGrid extends Model
{
    /** @use HasFactory<SupplierPriceGridFactory> */
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'equipment_category',
        'daily_rate',
        'weekly_rate',
        'monthly_rate',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'weekly_rate' => 'decimal:2',
        'monthly_rate' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(ThirdParty::class, 'supplier_id');
    }
}
