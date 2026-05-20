<?php

namespace App\Models\Commerce;

use App\Enums\Commerce\OrderStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'chantier_id',
        'purchase_request_id',
        'reference',
        'status',
        'total_ht',
        'total_ttc',
        'ordered_at',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'supplier_id');
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(ReceiptNote::class);
    }

    protected function casts(): array
    {
        return [
            'ordered_at' => 'timestamp',
            'status' => OrderStatus::class,
            'total_ht' => 'decimal:2',
            'total_ttc' => 'decimal:2',
        ];
    }
}
