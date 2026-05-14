<?php

namespace App\Models\RH;

use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'job_site_id', 'date', 'hours', 'type',
        'status', 'refusal_reason', 'approved_by_id', 'approved_at',
        'is_grand_deplacement', 'gd_allowance_amount', 'description',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
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
        ];
    }
}
