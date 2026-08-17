<?php

namespace App\Models\Locations;

use App\Enums\Locations\InternalRentalInvoiceStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Core\Company;
use App\Models\Immobilisation\FixedAsset;
use Database\Factories\Locations\InternalRentalInvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalRentalInvoice extends Model
{
    /** @use HasFactory<InternalRentalInvoiceFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'fixed_asset_id',
        'chantier_id',
        'period_start',
        'period_end',
        'days',
        'daily_rate',
        'amount_ht',
        'status',
        'billing_key',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'days' => 'integer',
            'daily_rate' => 'decimal:2',
            'amount_ht' => 'decimal:2',
            'status' => InternalRentalInvoiceStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }
}
