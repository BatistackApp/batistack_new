<?php

namespace App\Models\Commerce;

use App\Enums\Commerce\QuoteStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerQuote extends Model
{
    protected $fillable = [
        'client_id',
        'chantier_id',
        'reference',
        'status',
        'total_ht',
        'total_ttc',
        'signed_at',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'client_id');
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerQuoteItem::class);
    }

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'total_ht' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'signed_at' => 'datetime',
        ];
    }
}
