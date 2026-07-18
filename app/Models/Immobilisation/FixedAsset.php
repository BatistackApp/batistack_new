<?php

namespace App\Models\Immobilisation;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class FixedAsset extends Model
{
    use HasFactory;
    protected $fillable = [
        'asset_category_id',
        'name',
        'serial_number',
        'purchase_date',
        'purchase_price',
        'salvage_value',
        'depreciation_method',
        'useful_life_years',
        'status',
        'supplier_invoice_id',
        'vehicle_id',
        'chantier_id',
        'last_inventoried_at',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'last_inventoried_at' => 'datetime',
            'purchase_price' => 'decimal:2',
            'salvage_value' => 'decimal:2',
            'depreciation_method' => \App\Enums\Immobilisation\DepreciationMethod::class,
            'status' => \App\Enums\Immobilisation\AssetStatus::class,
        ];
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function depreciations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Depreciation::class);
    }

    public function disposal(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AssetDisposal::class);
    }

    public function supplierInvoice(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Commerce\SupplierInvoice::class);
    }

    public function vehicle(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Flottes\Vehicle::class);
    }

    public function chantier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Chantiers\Chantier::class);
    }

    public function maintenances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AssetMaintenance::class);
    }
}
