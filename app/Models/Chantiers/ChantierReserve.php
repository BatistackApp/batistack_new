<?php

namespace App\Models\Chantiers;

use App\Enums\Chantiers\ChantierReserveStatus;
use App\Enums\Chantiers\ReserveSeverity;
use App\Models\RH\Employee;
use App\Observers\Chantiers\ChantierReserveObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([ChantierReserveObserver::class])]
class ChantierReserve extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'chantier_id',
        'chantier_task_id',
        'assigned_to',
        'reference',
        'title',
        'description',
        'severity',
        'status',
        'due_date',
        'resolved_at',
        'lifted_at',
        'lifted_by',
    ];

    protected function casts(): array
    {
        return [
            'severity' => ReserveSeverity::class,
            'status' => ChantierReserveStatus::class,
            'due_date' => 'date',
            'resolved_at' => 'datetime',
            'lifted_at' => 'datetime',
        ];
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ChantierTask::class, 'chantier_task_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')->useDisk('public');
        $this->addMediaCollection('plan')->singleFile()->useDisk('public');
        $this->addMediaCollection('signature')->singleFile()->useDisk('public');
    }
}
