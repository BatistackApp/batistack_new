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
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

use Relaticle\ActivityLog\Concerns\InteractsWithTimeline;
use Relaticle\ActivityLog\Contracts\HasTimeline;
use Relaticle\ActivityLog\Timeline\TimelineBuilder;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

#[ObservedBy([CustomerInvoiceObserver::class])]
class CustomerInvoice extends Model implements HasTimeline
{
    use HasFactory, LogsActivity, InteractsWithTimeline;

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
        'total_tva',
        'dunning_level',
        'last_dunning_at',
        'stripe_session_id',
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

    public function allocations(): MorphMany
    {
        return $this->morphMany(PaymentAllocation::class, 'payable');
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
            'last_dunning_at' => 'datetime',
        ];
    }

    protected function isOverdue(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->status !== InvoiceStatus::PAID && $this->status !== InvoiceStatus::CANCELED) && Date::parse($this->due_date)->isPast(),
        );
    }

    /**
     * Montant total alloué (lettré) par tous les paiements
     *
     * Exemple:
     * $invoice->total_allocated; // 5000.00
     *
     * @return Attribute<float, never>
     */
    protected function totalAllocated(): Attribute
    {
        return Attribute::make(
            get: fn () => (float) $this->allocations()->sum('allocated_amount'),
        );
    }

    /**
     * Montant restant à payer
     *
     * Exemple:
     * $invoice->amount_remaining; // 5000.00 (si total 10k et 5k payé)
     *
     * @return Attribute<float, never>
     */
    protected function amountRemaining(): Attribute
    {
        return Attribute::make(
            get: fn () => max(0, (float) $this->total_ttc - $this->total_allocated),
        );
    }

    /**
     * Pourcentage de paiement
     *
     * Exemple:
     * $invoice->payment_percentage; // 50.0 (50% payée)
     *
     * @return Attribute<float, never>
     */
    protected function paymentPercentage(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total_ttc > 0
                ? ($this->total_allocated / $this->total_ttc) * 100
                : 0,
        );
    }

    /**
     * Vérifier si la facture est complètement payée
     *
     * Exemple:
     * if ($invoice->is_fully_paid) { ... }
     *
     * @return Attribute<bool, never>
     */
    protected function isFullyPaid(): Attribute
    {
        return Attribute::make(
            get: fn () => abs($this->amount_remaining - 0) < 0.05, // Tolérance 5 centimes
        );
    }

    /**
     * Vérifier si la facture est partiellement payée
     *
     * Exemple:
     * if ($invoice->is_partially_paid) { ... }
     *
     * @return Attribute<bool, never>
     */
    protected function isPartiallyPaid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total_allocated > 0 && ! $this->is_fully_paid,
        );
    }

    /**
     * Vérifier si la facture est impayée
     *
     * Exemple:
     * if ($invoice->is_unpaid) { ... }
     *
     * @return Attribute<bool, never>
     */
    protected function isUnpaid(): Attribute
    {
        return Attribute::make(
            get: fn () => abs($this->total_allocated) < 0.05,
        );
    }

    /**
     * Nombre de jours avant la deadline
     *
     * Exemple:
     * $invoice->days_until_due; // 10 (10 jours avant deadline)
     * $invoice->days_until_due; // -5 (5 jours de retard)
     *
     * @return Attribute<int, never>
     */
    protected function daysUntilDue(): Attribute
    {
        return Attribute::make(
            get: fn () => Date::parse($this->due_date)->diffInDays(now(), absolute: false),
        );
    }

    /**
     * Vérifier si la facture est surpayée (overpaid)
     *
     * Exemple:
     * if ($invoice->is_overpaid) { ... }
     *
     * @return Attribute<bool, never>
     */
    protected function isOverpaid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total_allocated > $this->total_ttc + 0.05,
        );
    }

    /**
     * Montant surpayé (positif si overpaid)
     *
     * Exemple:
     * $invoice->overpaid_amount; // 500.00
     *
     * @return Attribute<float, never>
     */
    protected function overpaidAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => max(0, $this->total_allocated - $this->total_ttc),
        );
    }

    /**
     * Filtrer les factures payées
     *
     * Exemple:
     * CustomerInvoice::paid()->get();
     */
    public function scopePaid($query)
    {
        return $query->where('status', InvoiceStatus::PAID);
    }

    /**
     * Filtrer les factures impayées
     *
     * Exemple:
     * CustomerInvoice::unpaid()->get();
     */
    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [InvoiceStatus::VALIDATED, InvoiceStatus::PARTIALLY_PAID])
            ->whereRaw('total_ttc > COALESCE((select sum(allocated_amount) from payment_allocations where payment_allocations.payable_id = customer_invoices.id and payment_allocations.payable_type = ?), 0)', [self::class]);
    }

    /**
     * Filtrer les factures en retard
     *
     * Exemple:
     * CustomerInvoice::overdue()->get();
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', [InvoiceStatus::PAID, InvoiceStatus::CANCELED])
            ->where('due_date', '<', now());
    }

    /**
     * Filtrer les factures éligibles à une relance spécifique
     *
     * @param int $days Nombre de jours de retard minimum requis
     * @param int $currentLevel Le niveau de relance actuel (ex: 0 pour passer à 1)
     */
    public function scopeEligibleForDunning($query, int $days, int $currentLevel)
    {
        return $query->unpaid()
            ->where('due_date', '<=', now()->subDays($days))
            ->where('dunning_level', $currentLevel);
    }

    /**
     * Filtrer les factures partiellement payées
     *
     * Exemple:
     * CustomerInvoice::partiallyPaid()->get();
     */
    public function scopePartiallyPaid($query)
    {
        return $query->where('status', InvoiceStatus::VALIDATED)
            ->withSum('allocations', 'allocated_amount')
            ->havingRaw('allocated_amount_sum > 0 AND allocated_amount_sum < total_ttc');
    }

    /**
     * Filtrer par client
     *
     * Exemple:
     * CustomerInvoice::forClient($client)->get();
     */
    public function scopeForClient($query, ThirdParty $client)
    {
        return $query->where('client_id', $client->id);
    }

    /**
     * Filtrer par chantier
     *
     * Exemple:
     * CustomerInvoice::forChantier($chantier)->get();
     */
    public function scopeForChantier($query, Chantier $chantier)
    {
        return $query->where('chantier_id', $chantier->id);
    }

    /**
     * Trier par date de facture (plus récente d'abord)
     *
     * Exemple:
     * CustomerInvoice::recent()->get();
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Inclure les allocations de paiement
     *
     * Exemple:
     * CustomerInvoice::withPayments()->get();
     */
    public function scopeWithPayments($query)
    {
        return $query->with('allocations.payment');
    }

    // ==================== METHODS ====================

    /**
     * Marquer la facture comme payée et mettre à jour le statut
     *
     * Utilisé après allocation de paiement
     */
    public function markAsPaid(): void
    {
        if ($this->is_fully_paid) {
            $this->update(['status' => InvoiceStatus::PAID]);
        }
    }

    /**
     * Marquer la facture comme impayée (rétrogradation)
     *
     * Utilisé après suppression d'une allocation
     */
    public function markAsUnpaid(): void
    {
        if ($this->is_unpaid) {
            $this->update(['status' => InvoiceStatus::VALIDATED]);
        }
    }

    /**
     * Obtenir la liste des paiements associés
     *
     * Exemple:
     * $payments = $invoice->getPayments();
     */
    public function getPayments(): Collection
    {
        return $this->allocations()
            ->with('payment')
            ->get()
            ->pluck('payment')
            ->unique('id');
    }

    /**
     * Obtenir le dernier paiement
     *
     * Exemple:
     * $lastPayment = $invoice->getLastPayment();
     *
     * @return Payment|null
     */
    public function getLastPayment()
    {
        return $this->allocations()
            ->latest('allocated_at')
            ->with('payment')
            ->first()
            ?->payment;
    }

    /**
     * Vérifier si la facture peut recevoir un paiement
     *
     * Exemple:
     * if ($invoice->canReceivePayment()) { ... }
     */
    public function canReceivePayment(): bool
    {
        return $this->status === InvoiceStatus::VALIDATED
            && $this->amount_remaining > 0;
    }

    /**
     * Calculer les jours de retard
     *
     * Exemple:
     * $days = $invoice->getDaysOverdue();
     */
    public function getDaysOverdue(): int
    {
        if (! $this->is_overdue) {
            return 0;
        }

        return Date::parse($this->due_date)->diffInDays(now());
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
