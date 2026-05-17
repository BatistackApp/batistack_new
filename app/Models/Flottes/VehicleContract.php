<?php

namespace App\Models\Flottes;

use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'supplier_id',
        'type',
        'policy_number',
        'start_date',
        'end_date',
        'annual_cost_ht',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'supplier_id');
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'annual_cost_ht' => 'decimal:2',
        ];
    }
}
