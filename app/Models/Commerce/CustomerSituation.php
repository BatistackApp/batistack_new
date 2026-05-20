<?php

namespace App\Models\Commerce;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Chantiers\Chantier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerSituation extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_order_id',
        'chantier_id',
        'number',
        'status',
        'total_ht',
        'retenue_garantie_amount',
        'prorata_amount',
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

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'total_ht' => 'decimal:2',
        ];
    }
}
