<?php

namespace App\Models\Chantiers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResourceAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'chantier_task_id',
        'allocatable_type',
        'allocatable_id',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function task(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ChantierTask::class, 'chantier_task_id');
    }

    public function allocatable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
