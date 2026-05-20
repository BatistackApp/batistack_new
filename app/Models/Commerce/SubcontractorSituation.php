<?php

namespace App\Models\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubcontractorSituation extends Model
{
    use HasFactory;

    protected $fillable = [
        'third_party_id',
        'chantier_id',
        'purchase_order_id',
        'reference',
        'progress_percentage',
        'total_ht',
        'retenue_garantie_amount',
        'status',
    ];

    public function subcontractor(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'subcontractor_id');
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'progress_percentage' => 'integer',
            'total_ht' => 'decimal:2',
            'retenue_garantie_amount' => 'decimal:2',
        ];
    }
}
