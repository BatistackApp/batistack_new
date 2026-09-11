<?php

namespace App\Models\Laser;

use App\Models\Tiers\ThirdParty;
use App\Observers\Laser\LaserCreditNoteObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([LaserCreditNoteObserver::class])]
class LaserCreditNote extends Model
{
    use HasFactory;

    protected static function boot(): void
    {
        parent::boot();

        static::updating(function (LaserCreditNote $creditNote) {
            $originalStatus = $creditNote->getOriginal('status');
            if ($creditNote->exists && $originalStatus === 'validated') {
                $dirtyKeys = array_keys($creditNote->getDirty());
                if (count($dirtyKeys) > 0) {
                    throw new \Exception('Un avoir validé ne peut pas être modifié.');
                }
            }
        });

        static::deleting(function (LaserCreditNote $creditNote) {
            $originalStatus = $creditNote->getOriginal('status');
            if ($originalStatus === 'validated') {
                throw new \Exception('Un avoir validé ne peut pas être supprimé.');
            }
        });
    }

    protected $fillable = [
        'client_id',
        'laser_invoice_id',
        'reference',
        'status',
        'total_ht',
        'total_tva',
        'total_ttc',
        'vat_rate',
        'reason',
        'signature_hash',
    ];

    protected function casts(): array
    {
        return [
            'total_ht' => 'decimal:2',
            'total_tva' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'vat_rate' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'client_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(LaserInvoice::class, 'laser_invoice_id');
    }
}
