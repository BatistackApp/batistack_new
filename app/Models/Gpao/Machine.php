<?php

namespace App\Models\Gpao;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'reference',
        'status',
        'usage_hours',
        'maintenance_interval_hours',
    ];

    protected function casts(): array
    {
        return [
            'status' => \App\Enums\Gpao\MachineStatus::class,
            'usage_hours' => 'decimal:2',
            'maintenance_interval_hours' => 'decimal:2',
        ];
    }

    public function maintenanceTickets(): HasMany
    {
        return $this->hasMany(MachineMaintenanceTicket::class);
    }

    public function manufacturingOrders(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ManufacturingOrder::class);
    }
}
