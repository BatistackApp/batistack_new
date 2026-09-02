<?php

namespace App\Models\Commerce;

use App\Enums\Commerce\QuoteStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\Concerns\DeletableWhenDraft;
use App\Models\Commerce\Concerns\RecalculatesTotals;
use App\Models\Core\Signature;
use App\Models\Tiers\ThirdParty;
use App\Models\User;
use App\Observers\Commerce\CustomerQuoteObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Relaticle\ActivityLog\Concerns\InteractsWithTimeline;
use Relaticle\ActivityLog\Contracts\HasTimeline;
use Relaticle\ActivityLog\Timeline\TimelineBuilder;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[ObservedBy([CustomerQuoteObserver::class])]
class CustomerQuote extends Model implements HasTimeline
{
    use DeletableWhenDraft, HasFactory, InteractsWithTimeline, LogsActivity, RecalculatesTotals;

    protected $fillable = [
        'client_id',
        'chantier_id',
        'parent_order_id',
        'reference',
        'status',
        'total_ht',
        'total_ttc',
        'signed_at',
        'expires_at',
        'responsable_id',
        'is_avenant',
        'counter_amount',
        'counter_message',
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

    public function order(): HasOne
    {
        return $this->hasOne(CustomerOrder::class);
    }

    /**
     * La commande principale à laquelle un avenant est rattaché.
     */
    public function parentOrder(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'parent_order_id');
    }

    public function signatures(): MorphMany
    {
        return $this->morphMany(Signature::class, 'signable');
    }

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'total_ht' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'signed_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_avenant' => 'boolean',
        ];
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at < now();
    }

    public function getTotalTvaAttribute(): float
    {
        $items = $this->items;
        $totalTva = 0;

        foreach ($items as $item) {
            $totalTva += $item->total_ht * ($item->vatRate->rate / 100);
        }

        return $totalTva;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function timeline(): TimelineBuilder
    {
        return TimelineBuilder::make($this)->fromActivityLog();
    }
}
