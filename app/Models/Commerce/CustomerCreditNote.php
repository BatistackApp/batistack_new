<?php

namespace App\Models\Commerce;

use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCreditNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'customer_invoice_id',
        'reference',
        'status',
        'total_ht',
        'total_ttc',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'client_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(CustomerInvoice::class, 'customer_invoice_id');
    }
}
