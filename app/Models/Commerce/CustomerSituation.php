<?php

namespace App\Models\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\Concerns\DeletableWhenDraft;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class CustomerSituation extends Model
{
    use DeletableWhenDraft, HasFactory;

    protected $fillable = [
        'customer_order_id',
        'chantier_id',
        'number',
        'status',
        'total_ht',
        'retenue_garantie_amount',
        'prorata_amount',
        'responsable_id',
        'periode_start',
        'periode_end',
        'situation_period',
        'total_tax',
        'total_ttc',
    ];

    protected $appends = [
        'situation_period',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerSituationItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function orderItem(): BelongsToMany
    {
        return $this->belongsToMany(
            CustomerOrderItem::class,
            'customer_situation_items',
            'customer_situation_id',
            'customer_order_item_id'
        )->withPivot(['progress_percentage', 'amount_ht']);
    }

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'total_ht' => 'decimal:2',
            'periode_start' => 'date',
            'periode_end' => 'date',
            'total_tax' => 'decimal:4',
            'total_ttc' => 'decimal:4',
        ];
    }

    protected function situationPeriod(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => [
                'periode_start' => $attributes['periode_start'] ?? null,
                'periode_end' => $attributes['periode_end'] ?? null,
            ],
            set: fn (?array $value) => [
                'periode_start' => $value['periode_start'] ? Carbon::parse($value['periode_start']) : null,
                'periode_end' => $value['periode_end'] ? Carbon::parse($value['periode_end']) : null,
            ]
        );
    }

    public function getAmountBilledBefore(): float
    {
        return self::where('customer_order_id', $this->customer_order_id)
            ->where('number', '<', $this->number)
            ->get()
            ->sum(function ($situation) {
                return $situation->total_ht + $situation->retenue_garantie_amount + $situation->prorata_amount;
            });
    }
}
