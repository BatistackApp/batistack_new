<?php

namespace App\Models\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierCreditNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'supplier_invoice_id',
        'reference',
        'status',
        'total_ht',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'supplier_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'total_ht' => 'decimal:2',
        ];
    }
}
