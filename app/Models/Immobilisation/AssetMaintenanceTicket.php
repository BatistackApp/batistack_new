<?php

namespace App\Models\Immobilisation;

use App\Enums\Immobilisation\AssetMaintenanceTicketStatus;
use App\Enums\Immobilisation\TicketSeverity;
use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use App\Observers\Immobilisation\AssetMaintenanceTicketObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([AssetMaintenanceTicketObserver::class])]
class AssetMaintenanceTicket extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'asset_type',
        'asset_id',
        'chantier_id',
        'reported_by_id',
        'reference',
        'description',
        'severity',
        'status',
        'cost_ht',
        'provider_name',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'severity' => TicketSeverity::class,
            'status' => AssetMaintenanceTicketStatus::class,
            'cost_ht' => 'decimal:2',
            'resolved_at' => 'datetime',
        ];
    }

    public function asset(): MorphTo
    {
        return $this->morphTo();
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reported_by_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')->useDisk('public');
    }
}
