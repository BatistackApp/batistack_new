<?php

namespace App\Models\RH;

use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Models\Chantiers\Chantier;
use App\Models\User;
use App\Observers\RH\TimeEntryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([TimeEntryObserver::class])]
class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'chantier_id', 'date', 'hours', 'type',
        'status', 'refusal_reason', 'approved_by_id', 'approved_at',
        'is_grand_deplacement', 'gd_allowance_amount', 'description',
        'travel_hours',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => TimeEntryType::class,
            'status' => TimeEntryStatus::class,
            'hours' => 'decimal:2',
            'is_grand_deplacement' => 'boolean',
            'gd_allowance_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'travel_hours' => 'decimal:2',
        ];
    }
}
