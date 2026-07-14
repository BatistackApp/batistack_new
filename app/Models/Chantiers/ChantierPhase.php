<?php

namespace App\Models\Chantiers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChantierPhase extends Model
{
    use HasFactory;

    protected $fillable = [
        'chantier_id',
        'label',
        'order',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function tasks(): HasMany { return $this->hasMany(ChantierTask::class)->orderBy('order'); }
}
