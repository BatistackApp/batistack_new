<?php

namespace App\Models\RH;

use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\WeatherAlert;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CibtpDeclaration extends Model
{
    use HasFactory;

    protected $fillable = [
        'chantier_id',
        'weather_alert_id',
        'date',
        'status',
        'total_lost_hours',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_lost_hours' => 'decimal:2',
        ];
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function weatherAlert(): BelongsTo
    {
        return $this->belongsTo(WeatherAlert::class);
    }
}
