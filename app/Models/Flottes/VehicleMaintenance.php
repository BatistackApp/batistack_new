<?php

namespace App\Models\Flottes;

use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use App\Observers\Flottes\VehicleMaintenanceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([VehicleMaintenanceObserver::class])]
class VehicleMaintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'supplier_id',
        'vat_rate_id',
        'type',
        'description',
        'cost_ht',
        'odometer_at_maintenance',
        'performed_at',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'supplier_id');
    }

    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }

    protected function casts(): array
    {
        return [
            'cost_ht' => 'decimal:4',
            'odometer_at_maintenance' => 'decimal:2',
            'performed_at' => 'date',
        ];
    }
}
