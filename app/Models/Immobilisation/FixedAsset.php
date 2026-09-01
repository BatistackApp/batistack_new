<?php

namespace App\Models\Immobilisation;

use App\Enums\Immobilisation\AssetStatus;
use App\Enums\Immobilisation\DepreciationMethod;
use App\Enums\Immobilisation\GrantMethod;
use App\Enums\Locations\RentalBillingPeriod;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierEquipmentTracking;
use App\Models\Commerce\SupplierInvoice;
use App\Models\Flottes\Vehicle;
use App\Models\Locations\InternalRentalInvoice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class FixedAsset extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * Propriété transitoire (non persistée) permettant de transmettre un motif
     * de libération au niveau de l'observer.
     */
    public ?string $release_reason = null;

    protected $fillable = [
        'asset_category_id',
        'name',
        'serial_number',
        'purchase_date',
        'purchase_price',
        'daily_rate',
        'internal_rental_period',
        'salvage_value',
        'depreciation_method',
        'useful_life_years',
        'status',
        'supplier_invoice_id',
        'vehicle_id',
        'chantier_id',
        'last_inventoried_at',
        'vgp_frequency_months',
        'grant_amount',
        'grant_name',
        'grant_method',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'last_inventoried_at' => 'datetime',
            'purchase_price' => 'decimal:2',
            'salvage_value' => 'decimal:2',
            'depreciation_method' => DepreciationMethod::class,
            'grant_method' => GrantMethod::class,
            'status' => AssetStatus::class,
            'daily_rate' => 'decimal:2',
            'internal_rental_period' => RentalBillingPeriod::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(Depreciation::class);
    }

    public function disposal(): HasOne
    {
        return $this->hasOne(AssetDisposal::class);
    }

    public function supplierInvoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return HasMany<FixedAssetAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(FixedAssetAssignment::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function internalRentalInvoices(): HasMany
    {
        return $this->hasMany(InternalRentalInvoice::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(AssetMaintenance::class);
    }

    public function impairments(): HasMany
    {
        return $this->hasMany(AssetImpairment::class);
    }

    public function maintenanceTickets(): MorphMany
    {
        return $this->morphMany(AssetMaintenanceTicket::class, 'asset');
    }

    public function trackings(): MorphMany
    {
        return $this->morphMany(ChantierEquipmentTracking::class, 'trackable');
    }

    public function getNextVgpDateAttribute(): ?Carbon
    {
        if (! $this->vgp_frequency_months) {
            return null;
        }

        // Chercher la dernière maintenance de type 'control'
        $lastControl = $this->maintenances()->where('type', 'control')->latest('maintenance_date')->first();

        if ($lastControl) {
            return Carbon::parse($lastControl->maintenance_date)->addMonths($this->vgp_frequency_months);
        }

        // Si jamais fait, on se base sur la date d'achat
        if ($this->purchase_date) {
            return Carbon::parse($this->purchase_date)->addMonths($this->vgp_frequency_months);
        }

        return null;
    }

    public function getVgpStatusAttribute(): string
    {
        if (! $this->vgp_frequency_months) {
            return 'none';
        }

        $nextDate = $this->next_vgp_date;

        if (! $nextDate) {
            return 'none';
        }

        if ($nextDate->isPast()) {
            return 'danger';
        }

        if ($nextDate->copy()->subDays(30)->isPast()) {
            return 'warning';
        }

        return 'ok';
    }
}
