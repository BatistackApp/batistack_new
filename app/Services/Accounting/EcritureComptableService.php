<?php

namespace App\Services\Accounting;

use App\Enums\Accounting\JournalType;
use App\Enums\Accounting\LettrageStatus;
use App\Models\Accounting\EcritureComptable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EcritureComptableService
{
    public function createEntry(array $data): EcritureComptable
    {
        return EcritureComptable::create($data);
    }

    public function createBalancedPair(array $dataDebit, array $dataCredit): array
    {
        $amount = $dataDebit['debit'] ?? $dataCredit['credit'] ?? 0;

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Le montant doit être supérieur à 0.');
        }

        $ecritureDebit = EcritureComptable::create(array_merge($dataDebit, [
            'debit' => $amount,
            'credit' => 0,
        ]));

        $ecritureCredit = EcritureComptable::create(array_merge($dataCredit, [
            'debit' => 0,
            'credit' => $amount,
        ]));

        return [$ecritureDebit, $ecritureCredit];
    }

    public function lettrer(Collection $ecritures, string $code): void
    {
        if ($ecritures->isEmpty()) {
            throw new \InvalidArgumentException('Aucune écriture à lettrer.');
        }

        $totalDebit = $ecritures->sum('debit');
        $totalCredit = $ecritures->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new \InvalidArgumentException(
                "Les écritures ne sont pas équilibrées. Débit: {$totalDebit}, Crédit: {$totalCredit}"
            );
        }

        DB::transaction(function () use ($ecritures, $code) {
            foreach ($ecritures as $ecriture) {
                $ecriture->update([
                    'lettrage' => $code,
                    'lettrage_status' => LettrageStatus::LETTRÉE,
                ]);
            }
        });
    }

    public function dellettrer(Collection $ecritures): void
    {
        DB::transaction(function () use ($ecritures) {
            foreach ($ecritures as $ecriture) {
                $ecriture->update([
                    'lettrage' => null,
                    'lettrage_status' => LettrageStatus::NON_LETTRÉE,
                ]);
            }
        });
    }

    public function getSoldeCompte(string $compteNumero, string $dateFrom = null, string $dateTo = null): float
    {
        $query = EcritureComptable::where('compte_numero', $compteNumero);

        if ($dateFrom) {
            $query->where('date_ecriture', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('date_ecriture', '<=', $dateTo);
        }

        $totalDebit = (float) $query->sum('debit');
        $totalCredit = (float) $query->sum('credit');

        return $totalDebit - $totalCredit;
    }

    public function getSoldeJournal(JournalType $journal, string $dateFrom = null, string $dateTo = null): float
    {
        $query = EcritureComptable::where('journal_type', $journal);

        if ($dateFrom) {
            $query->where('date_ecriture', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('date_ecriture', '<=', $dateTo);
        }

        $totalDebit = (float) $query->sum('debit');
        $totalCredit = (float) $query->sum('credit');

        return $totalDebit - $totalCredit;
    }

    public function getBalanceCompte(string $compteNumero): array
    {
        $ecritures = EcritureComptable::where('compte_numero', $compteNumero)->get();

        return [
            'compte' => $compteNumero,
            'total_debit' => (float) $ecritures->sum('debit'),
            'total_credit' => (float) $ecritures->sum('credit'),
            'solde' => (float) $ecritures->sum('debit') - (float) $ecritures->sum('credit'),
            'nombre_ecritures' => $ecritures->count(),
        ];
    }

    public function generateNumeroPiece(JournalType $journal): string
    {
        $code = $journal->getCode();
        $prefix = $code . '-' . date('Ymd');

        $last = EcritureComptable::where('numero_piece', 'LIKE', $prefix . '%')
            ->orderByDesc('numero_piece')
            ->value('numero_piece');

        if ($last) {
            $seq = (int) substr($last, -4) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
