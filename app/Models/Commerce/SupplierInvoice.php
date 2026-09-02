<?php

namespace App\Models\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\Concerns\DeletableWhenDraft;
use App\Models\Immobilisation\FixedAsset;
use App\Models\Tiers\ThirdParty;
use App\Observers\Commerce\SupplierInvoiceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[ObservedBy([SupplierInvoiceObserver::class])]
class SupplierInvoice extends Model
{
    use DeletableWhenDraft, HasFactory;

    protected $fillable = [
        'supplier_id',
        'purchase_order_id',
        'reference',
        'amount_ht',
        'amount_ttc',
        'status',
        'dispute_reason',
        'due_date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'supplier_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function allocations(): MorphMany
    {
        return $this->morphMany(PaymentAllocation::class, 'payable');
    }

    public function fixedAssets(): HasMany
    {
        return $this->hasMany(FixedAsset::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierInvoiceItem::class);
    }

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'amount_ht' => 'decimal:2',
            'amount_ttc' => 'decimal:2',
            'due_date' => 'datetime',
        ];
    }

    public function getAmountRemainingAttribute(): float
    {
        if ($this->status === InvoiceStatus::PAID) {
            return 0.0;
        }

        $allocated = $this->allocations()->sum('allocated_amount');

        return max(0.0, (float) $this->amount_ttc - (float) $allocated);
    }
}
