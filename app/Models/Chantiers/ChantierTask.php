<?php

namespace App\Models\Chantiers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChantierTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'chantier_phase_id',
        'label',
        'description',
        'order',
        'estimated_hours',
        'progress_percentage',
        'is_completed',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ChantierPhase::class, 'chantier_phase_id');
    }

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'progress_percentage' => 'integer',
            'estimated_hours' => 'decimal:2',
        ];
    }
}
