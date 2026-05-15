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
    ];

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function tasks(): HasMany { return $this->hasMany(ChantierTask::class)->orderBy('order'); }
}
