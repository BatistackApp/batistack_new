<?php

namespace App\Models\Chantiers;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ChantierLog extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'chantier_id',
        'user_id',
        'date',
        'content',
        'weather_condition',
        'incident_reported',
    ];

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'incident_reported' => 'boolean',
        ];
    }
}
