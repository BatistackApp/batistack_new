<?php

namespace App\Models\RH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Enums\RH\InterviewType;
use App\Enums\RH\InterviewStatus;
use App\Models\User;
use App\Models\RH\Employee;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\RH\InterviewFactory> */
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
