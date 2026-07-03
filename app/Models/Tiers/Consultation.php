<?php

namespace App\Models\Tiers;

use Illuminate\Database\Eloquent\Model;
use App\Models\Chantiers\Chantier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Consultation extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'chantier_id',
        'title',
        'description',
        'deadline',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
        ];
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(ConsultationOffer::class);
    }
}
