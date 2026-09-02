<?php

namespace App\Models\Interventions;

use App\Models\Flottes\Vehicle;
use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterventionGpsTrack extends Model
{
    protected $fillable = [
        'intervention_id',
        'employee_id',
        'vehicle_id',
        'latitude',
        'longitude',
        'altitude',
        'accuracy',
        'speed',
        'heading',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'altitude' => 'float',
        'accuracy' => 'float',
        'speed' => 'float',
        'heading' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(Intervention::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function scopeForIntervention(Builder $query, int $interventionId): Builder
    {
        return $query->where('intervention_id', $interventionId);
    }

    public function scopeRecent(Builder $query, int $minutes = 30): Builder
    {
        return $query->where('recorded_at', '>=', now()->subMinutes($minutes));
    }
}
