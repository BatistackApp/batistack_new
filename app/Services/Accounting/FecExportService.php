<?php

namespace App\Services\Accounting;

use App\Models\Accounting\EcritureComptable;
use Illuminate\Support\Facades\Storage;

class FecExportService
{
    /**
     * Export FEC 18 colonnes depuis les écritures comptables.
     */
    public function exportFec(int $year, string $siren = '123456789'): string
    {
        $ecritures = EcritureComptable::with('compte')
            ->whereYear('date_ecriture', $year)
            ->orderBy('date_ecriture')
            ->orderBy('journal_type')
            ->orderBy('numero_piece')
            ->get();

        $header = [
            'JournalCode',
            'JournalLib',
            'EcritureNum',
            'EcritureDate',
            'CompteNum',
            'CompteLib',
            'CompAuxNum',
            'CompAuxLib',
            'PieceRef',
            'PieceDate',
            'EcritureLib',
            'Debit',
            'Credit',
            'EcritureLet',
            'DateLet',
            'ValidDate',
            'Montantdevise',
            'Idevise',
        ];

        $lines = [implode("\t", $header)];

        foreach ($ecritures as $e) {
            $dateStr = $e->date_ecriture->format('Ymd');
            $pieceDateStr = $e->date_piece->format('Ymd');
            $compteLib = $e->compte?->libelle ?? '';
            $montantDebit = number_format((float) $e->debit, 2, ',', '');
            $montantCredit = number_format((float) $e->credit, 2, ',', '');

            $lines[] = implode("\t", [
                $e->journal_type->getCode(),
                $e->journal_type->getLabel(),
                $e->numero_piece,
                $dateStr,
                $e->compte_numero,
                $compteLib,
                '',
                '',
                $e->numero_piece,
                $pieceDateStr,
                $e->libelle,
                $montantDebit,
                $montantCredit,
                $e->lettrage ?? '',
                '',
                $dateStr,
                '',
                '',
            ]);
        }

        $content = implode("\r\n", $lines);

        $filename = $siren.'FEC'.$year.'1231.txt';
        $relativePath = 'exports/fec/'.$filename;

        Storage::disk('local')->put($relativePath, $content);

        return Storage::disk('local')->path($relativePath);
    }

    /**
     * Génère le fichier FEC pour les amortissements d'une année donnée (legacy).
     */
    public function exportDepreciationsFec(int $year): string
    {
        $ecritures = EcritureComptable::with('compte')
            ->whereYear('date_ecriture', $year)
            ->where('journal_type', 'ano')
            ->orderBy('date_ecriture')
            ->get();

        if ($ecritures->isEmpty()) {
            return $this->exportFec($year);
        }

        return $this->exportFec($year);
    }

    /**
     * Retourne les données FEC format array pour preview.
     */
    public function getFecData(int $year): array
    {
        $ecritures = EcritureComptable::with('compte')
            ->whereYear('date_ecriture', $year)
            ->orderBy('date_ecriture')
            ->orderBy('journal_type')
            ->get();

        $header = [
            'JournalCode', 'JournalLib', 'EcritureNum', 'EcritureDate',
            'CompteNum', 'CompteLib', 'CompAuxNum', 'CompAuxLib',
            'PieceRef', 'PieceDate', 'EcritureLib', 'Debit', 'Credit',
            'EcritureLet', 'DateLet', 'ValidDate', 'Montantdevise', 'Idevise',
        ];

        $rows = [];
        foreach ($ecritures as $e) {
            $rows[] = [
                'JournalCode' => $e->journal_type->getCode(),
                'JournalLib' => $e->journal_type->getLabel(),
                'EcritureNum' => $e->numero_piece,
                'EcritureDate' => $e->date_ecriture->format('d/m/Y'),
                'CompteNum' => $e->compte_numero,
                'CompteLib' => $e->compte?->libelle ?? '',
                'CompAuxNum' => '',
                'CompAuxLib' => '',
                'PieceRef' => $e->numero_piece,
                'PieceDate' => $e->date_piece->format('d/m/Y'),
                'EcritureLib' => $e->libelle,
                'Debit' => (float) $e->debit,
                'Credit' => (float) $e->credit,
                'EcritureLet' => $e->lettrage ?? '',
                'DateLet' => '',
                'ValidDate' => $e->date_ecriture->format('d/m/Y'),
                'Montantdevise' => '',
                'Idevise' => '',
            ];
        }

        return ['header' => $header, 'rows' => $rows];
    }

    /**
     * Calcule la balance générale par compte.
     */
    public function getBalanceGenerale(int $year): array
    {
        $ecritures = EcritureComptable::whereYear('date_ecriture', $year)->get();

        $balance = [];
        foreach ($ecritures as $e) {
            if (! isset($balance[$e->compte_numero])) {
                $balance[$e->compte_numero] = [
                    'compte' => $e->compte_numero,
                    'libelle' => $e->compte?->libelle ?? '',
                    'classe' => $e->compte?->classe ?? 0,
                    'total_debit' => 0.0,
                    'total_credit' => 0.0,
                    'solde_debit' => 0.0,
                    'solde_credit' => 0.0,
                ];
            }
            $balance[$e->compte_numero]['total_debit'] += (float) $e->debit;
            $balance[$e->compte_numero]['total_credit'] += (float) $e->credit;
        }

        foreach ($balance as &$row) {
            $solde = $row['total_debit'] - $row['total_credit'];
            if ($solde >= 0) {
                $row['solde_debit'] = $solde;
            } else {
                $row['solde_credit'] = abs($solde);
            }
        }
        unset($row);

        usort($balance, fn ($a, $b) => strcmp($a['compte'], $b['compte']));

        return $balance;
    }
}
