<?php

namespace App\Models\Flottes;

use App\Enums\Flottes\ConditionReportType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class VehicleConditionReport extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'vehicle_assignment_id',
        'type',
        'odometer',
        'fuel_level',
        'signature_checksum',
        'signed_at',
        'comment',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(VehicleAssignment::class, 'vehicle_assignment_id');
    }

    protected function casts(): array
    {
        return [
            'odometer' => 'decimal:2',
            'fuel_level' => 'integer',
            'signed_at' => 'datetime',
            'type' => ConditionReportType::class,
        ];
    }

    /**
     * Configuration des collections d'images Spatie MediaLibrary.
     * On isole chaque face et le tableau de bord pour un contrôle précis.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo_front')->singleFile();
        $this->addMediaCollection('photo_back')->singleFile();
        $this->addMediaCollection('photo_left')->singleFile();
        $this->addMediaCollection('photo_right')->singleFile();
        $this->addMediaCollection('photo_dashboard')->singleFile(); // Odomètre & Jauge carburant
    }
}
