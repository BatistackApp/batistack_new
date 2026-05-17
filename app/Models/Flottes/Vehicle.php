<?php

namespace App\Models\Flottes;

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Enums\Flottes\VehicleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Vehicle extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'reference',
        'license_plate',
        'brand',
        'model',
        'type',
        'fuel_type',
        'odometer',
        'status',
        'current_location',
        'daily_rate',
        'km_rate',
        'purchase_date',
        'purchase_price',
    ];

    protected function casts(): array
    {
        return [
            'type' => VehicleType::class,
            'status' => VehicleStatus::class,
            'odometer' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'km_rate' => 'decimal:4',
            'purchase_date' => 'date',
            'purchase_price' => 'decimal:2',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(VehicleAssignment::class);
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(VehicleAssignment::class)->where('status', AssignmentStatus::ACTIVE);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(VehicleMaintenance::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(VehicleContract::class);
    }

    public function fines(): HasMany
    {
        return $this->hasMany(TrafficFine::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('registration_card')->singleFile(); // Carte grise
        $this->addMediaCollection('photos'); // Galerie état du véhicule
    }
}
