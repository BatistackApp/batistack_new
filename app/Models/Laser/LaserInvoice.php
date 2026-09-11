<?php

namespace App\Models\Laser;

use App\Enums\Laser\InvoiceStatus;
use App\Models\Laser\Concerns\RecalculatesLaserTotals;
use App\Models\Tiers\ThirdParty;
use App\Observers\Laser\LaserInvoiceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([LaserInvoiceObserver::class])]
class LaserInvoice extends Model
{
    use HasFactory, RecalculatesLaserTotals;

    protected $fillable = [
        'client_id',
        'laser_order_id',
        'reference',
        'status',
        'total_ht',
        'total_tva',
        'total_ttc',
        'vat_rate',
        'credited_amount_ht',
        'credited_amount_tva',
        'credited_amount_ttc',
        'due_date',
        'signature_hash',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::updating(function (LaserInvoice $invoice) {
            if ($invoice->exists && in_array($invoice->status, [InvoiceStatus::VALIDATED, InvoiceStatus::PAID])) {
                $dirtyKeys = array_keys($invoice->getDirty());
                $allowedUpdates = ['credited_amount_ht', 'credited_amount_tva', 'credited_amount_ttc'];
                if (count(array_diff($dirtyKeys, $allowedUpdates)) > 0) {
                    throw new \Exception('Une facture validée ou payée ne peut pas être modifiée.');
                }
            }
        });

        static::deleting(function (LaserInvoice $invoice) {
            if (in_array($invoice->status, [InvoiceStatus::VALIDATED, InvoiceStatus::PAID])) {
                throw new \Exception('Une facture validée ou payée ne peut pas être supprimée.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'total_ht' => 'decimal:2',
            'total_tva' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'credited_amount_ht' => 'decimal:2',
            'credited_amount_tva' => 'decimal:2',
            'credited_amount_ttc' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'client_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(LaserOrder::class, 'laser_order_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(LaserInvoiceLine::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(LaserCreditNote::class);
    }

    public function getRemainingCreditableHtAttribute(): float
    {
        return (float) $this->total_ht - (float) $this->credited_amount_ht;
    }

    public function getRemainingCreditableTtcAttribute(): float
    {
        return (float) $this->total_ttc - (float) $this->credited_amount_ttc;
    }

    public function canBeDeleted(): bool
    {
        return $this->status->value === InvoiceStatus::DRAFT->value;
    }

    public function canBeCredited(): bool
    {
        return in_array($this->status, [InvoiceStatus::VALIDATED, InvoiceStatus::PAID])
            && $this->remaining_creditable_ht > 0;
    }
}
