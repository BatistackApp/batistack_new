<?php

namespace App\Models\Flottes;

use App\Enums\Flottes\FineStatus;
use App\Models\RH\Employee;
use App\Observers\Flottes\TrafficFineObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([TrafficFineObserver::class])]
class TrafficFine extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'employee_id',
        'reference',
        'infraction_at',
        'amount',
        'points_deducted',
        'status',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected function casts(): array
    {
        return [
            'infraction_at' => 'datetime',
            'amount' => 'decimal:2',
            'status' => FineStatus::class,
        ];
    }
}
