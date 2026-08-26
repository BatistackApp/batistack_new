<?php

namespace App\Services\Accounting;

use App\Models\Accounting\EcritureComptable;
use Illuminate\Support\Facades\Storage;

class Sage50ExportService
{
    /**
     * Export Sage 50 CSV format.
     * Format: JournalCode;JournalLib;EcritureNum;EcritureDate;CompteNum;CompteLib;PieceRef;PieceDate;EcritureLib;Debit;Credit;EcritureLet
     */
    public function exportCsv(int $year, string $separator = ';'): string
    {
        $ecritures = EcritureComptable::with('compte')
            ->whereYear('date_ecriture', $year)
            ->orderBy('date_ecriture')
            ->orderBy('journal_type')
            ->get();

        $header = [
            'JournalCode',
            'JournalLib',
            'EcritureNum',
            'EcritureDate',
            'CompteNum',
            'CompteLib',
            'PieceRef',
            'PieceDate',
            'EcritureLib',
            'Debit',
            'Credit',
            'EcritureLet',
        ];

        $lines = [implode($separator, $header)];

        foreach ($ecritures as $e) {
            $lines[] = implode($separator, [
                $e->journal_type->getCode(),
                $e->journal_type->getLabel(),
                $e->numero_piece,
                $e->date_ecriture->format('d/m/Y'),
                $e->compte_numero,
                $e->compte?->libelle ?? '',
                $e->numero_piece,
                $e->date_piece->format('d/m/Y'),
                $e->libelle,
                number_format((float) $e->debit, 2, '.', ''),
                number_format((float) $e->credit, 2, '.', ''),
                $e->lettrage ?? '',
            ]);
        }

        $content = implode("\r\n", $lines);

        $filename = 'export_sage50_'.$year.'_'.date('Ymd_His').'.csv';
        $relativePath = 'exports/accounting/'.$filename;

        Storage::disk('local')->put($relativePath, $content);

        return Storage::disk('local')->path($relativePath);
    }

    /**
     * Retourne les données Sage 50 format array pour preview.
     */
    public function getData(int $year): array
    {
        $ecritures = EcritureComptable::with('compte')
            ->whereYear('date_ecriture', $year)
            ->orderBy('date_ecriture')
            ->get();

        $header = [
            'JournalCode', 'JournalLib', 'EcritureNum', 'EcritureDate',
            'CompteNum', 'CompteLib', 'PieceRef', 'PieceDate',
            'EcritureLib', 'Debit', 'Credit', 'EcritureLet',
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
                'PieceRef' => $e->numero_piece,
                'PieceDate' => $e->date_piece->format('d/m/Y'),
                'EcritureLib' => $e->libelle,
                'Debit' => (float) $e->debit,
                'Credit' => (float) $e->credit,
                'EcritureLet' => $e->lettrage ?? '',
            ];
        }

        return ['header' => $header, 'rows' => $rows];
    }
}
