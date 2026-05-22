<?php

namespace App\Models\Commerce;

use App\Enums\Commerce\OrderStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'chantier_id',
        'customer_quote_id',
        'reference',
        'status',
        'total_ht',
        'total_ttc',
        'responsable_id',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'client_id');
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(CustomerQuote::class, 'customer_quote_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerOrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function deliveryNotes(): HasMany
    {
        return $this->hasMany(CustomerDeliveryNote::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CustomerInvoice::class);
    }

    public function situations(): HasMany
    {
        return $this->hasMany(CustomerSituation::class);
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total_ht' => 'decimal:2',
            'total_ttc' => 'decimal:2',
        ];
    }
}
