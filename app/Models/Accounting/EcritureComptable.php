<?php

namespace App\Models\Accounting;

use App\Enums\Accounting\JournalType;
use App\Enums\Accounting\LettrageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcritureComptable extends Model
{
    use HasFactory;

    protected $fillable = [
        'date_ecriture',
        'date_piece',
        'journal_type',
        'numero_piece',
        'compte_numero',
        'libelle',
        'debit',
        'credit',
        'lettrage',
        'lettrage_status',
        'reconcilable_type',
        'reconcilable_id',
        'chantier_id',
    ];

    protected $casts = [
        'date_ecriture' => 'date',
        'date_piece' => 'date',
        'journal_type' => JournalType::class,
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'lettrage_status' => LettrageStatus::class,
    ];

    protected static function booted(): void
    {
        static::saving(function (EcritureComptable $ecriture) {
            if ($ecriture->debit > 0 && $ecriture->credit > 0) {
                throw new \InvalidArgumentException('Une écriture ne peut pas avoir à la fois un débit et un crédit.');
            }
            if ($ecriture->debit == 0 && $ecriture->credit == 0) {
                throw new \InvalidArgumentException('Une écriture doit avoir un débit ou un crédit non nul.');
            }
        });
    }

    public function compte(): BelongsTo
    {
        return $this->belongsTo(CompteComptable::class, 'compte_numero', 'numero');
    }

    public function getMontantAttribute(): float
    {
        return (float) $this->debit - (float) $this->credit;
    }

    public function getIsDebitAttribute(): bool
    {
        return (float) $this->debit > 0;
    }

    public function getIsCreditAttribute(): bool
    {
        return (float) $this->credit > 0;
    }

    public function scopeDeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('date_ecriture', [$from, $to]);
    }

    public function scopeDuJournal($query, JournalType $type)
    {
        return $query->where('journal_type', $type);
    }

    public function scopeNonLettrées($query)
    {
        return $query->where('lettrage_status', LettrageStatus::NON_LETTRÉE);
    }

    public function scopeLettrées($query)
    {
        return $query->where('lettrage_status', LettrageStatus::LETTRÉE);
    }

    public function scopeDuChantier($query, int $chantierId)
    {
        return $query->where('chantier_id', $chantierId);
    }

    public function isBalanced(): bool
    {
        return abs((float) $this->debit - (float) $this->credit) < 0.01;
    }
}
