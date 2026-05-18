<?php

namespace App\Models\Flottes;

use App\Models\Articles\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'item_id',
        'serial_number',
        'quantity',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
