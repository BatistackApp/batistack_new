<?php

namespace App\Models\RH;

use App\Enums\RH\InterviewStatus;
use App\Enums\RH\InterviewType;
use App\Models\User;
use Database\Factories\RH\InterviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Interview extends Model implements HasMedia
{
    /** @use HasFactory<InterviewFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'employee_id',
        'manager_id',
        'type',
        'status',
        'scheduled_at',
        'evaluation_grid',
        'employee_signature',
        'manager_signature',
    ];

    protected $casts = [
        'type' => InterviewType::class,
        'status' => InterviewStatus::class,
        'scheduled_at' => 'datetime',
        'evaluation_grid' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
