<?php

namespace App\Models\Banque;

use App\Enums\Banque\TransactionStatus;
use App\Enums\Banque\TransactionType;
use App\Models\Chantiers\Chantier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankTransaction extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::creating(function ($transaction) {
            if (empty($transaction->external_id)) {
                $transaction->external_id = 'MANUAL-'.uniqid();
            }
        });
    }

    protected $fillable = [
        'bank_account_id',
        'date',
        'description',
        'amount',
        'type',
        'status',
        'external_id',
        'transaction_category_id',
        'chantier_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'transaction_category_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliation::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function scopeIncomes($query)
    {
        return $query->where('type', TransactionType::CREDIT);
    }

    public function scopeExpenses($query)
    {
        return $query->where('type', TransactionType::DEBIT);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    public function scopeLastSixMonths($query)
    {
        return $query->where('date', '>=', now()->subMonths(6)->startOfMonth());
    }
}
