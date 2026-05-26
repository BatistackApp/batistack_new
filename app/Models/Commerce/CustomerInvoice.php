<?php

namespace App\Models\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use App\Models\Chantiers\Chantier;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Observers\Commerce\CustomerInvoiceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Date;

#[ObservedBy([CustomerInvoiceObserver::class])]
class CustomerInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'chantier_id',
        'customer_order_id',
        'customer_situation_id',
        'reference',
        'type',
        'status',
        'total_ht',
        'total_ttc',
        'due_date',
        'cancellation_reason',
        'responsable_id',
        'sent_at',
        'signature_hash',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'client_id');
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    public function situation(): BelongsTo
    {
        return $this->belongsTo(CustomerSituation::class, 'customer_situation_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerInvoiceItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'status' => InvoiceStatus::class,
            'total_ht' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'due_date' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function getTotalTvaAttribute(): float
    {
        $items = $this->items;
        $totalTva = 0;

        foreach ($items as $item) {
            $totalTva += $item->selling_price * $item->vatRate->rate;
        }

        return $totalTva;
    }

    protected function isOverdue(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->status !== InvoiceStatus::PAID && $this->status !== InvoiceStatus::CANCELED) && Date::parse($this->due_date)->isPast(),
        );
    }
}
