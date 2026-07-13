<?php

namespace App\Models\Banque;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BankReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_transaction_id',
        'reconcilable_type',
        'reconcilable_id',
        'amount_applied',
    ];

    protected $casts = [
        'amount_applied' => 'decimal:2',
    ];

    public function bankTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class);
    }

    public function reconcilable(): MorphTo
    {
        return $this->morphTo();
    }
}
