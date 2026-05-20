<?php

namespace App\Models\Commerce;

use App\Enums\Commerce\QuoteStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Observers\Commerce\CustomerQuoteObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([CustomerQuoteObserver::class])]
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
        'expires_at',
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

    public function items(): HasMany
    {
        return $this->hasMany(CustomerQuoteItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'total_ht' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'signed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
