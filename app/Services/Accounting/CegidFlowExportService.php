<?php

namespace App\Services\Accounting;

use App\Models\Accounting\EcritureComptable;
use Illuminate\Support\Facades\Storage;

class CegidFlowExportService
{
    /**
     * Export Cegid Flow CSV format.
     * Format: JournalCode;EcritureDate;CompteNum;CompteAuxNum;EcritureLib;Debit;Credit;PieceRef;EcritureLet;Devise;Lettrage
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
            'EcritureDate',
            'CompteNum',
            'CompteAuxNum',
            'EcritureLib',
            'Debit',
            'Credit',
            'PieceRef',
            'EcritureLet',
            'Devise',
            'Lettrage',
        ];

        $lines = [implode($separator, $header)];

        foreach ($ecritures as $e) {
            $lines[] = implode($separator, [
                $e->journal_type->getCode(),
                $e->date_ecriture->format('d/m/Y'),
                $e->compte_numero,
                '',
                $e->libelle,
                number_format((float) $e->debit, 2, '.', ''),
                number_format((float) $e->credit, 2, '.', ''),
                $e->numero_piece,
                $e->lettrage ?? '',
                'EUR',
                $e->lettrage ?? '',
            ]);
        }

        $content = implode("\r\n", $lines);

        $filename = 'export_cegid_flow_' . $year . '_' . date('Ymd_His') . '.csv';
        $relativePath = 'exports/accounting/' . $filename;

        Storage::disk('local')->put($relativePath, $content);

        return Storage::disk('local')->path($relativePath);
    }

    /**
     * Retourne les données Cegid Flow format array pour preview.
     */
    public function getData(int $year): array
    {
        $ecritures = EcritureComptable::with('compte')
            ->whereYear('date_ecriture', $year)
            ->orderBy('date_ecriture')
            ->get();

        $header = [
            'JournalCode', 'EcritureDate', 'CompteNum', 'CompteAuxNum',
            'EcritureLib', 'Debit', 'Credit', 'PieceRef', 'EcritureLet',
            'Devise', 'Lettrage',
        ];

        $rows = [];
        foreach ($ecritures as $e) {
            $rows[] = [
                'JournalCode' => $e->journal_type->getCode(),
                'EcritureDate' => $e->date_ecriture->format('d/m/Y'),
                'CompteNum' => $e->compte_numero,
                'CompteAuxNum' => '',
                'EcritureLib' => $e->libelle,
                'Debit' => (float) $e->debit,
                'Credit' => (float) $e->credit,
                'PieceRef' => $e->numero_piece,
                'EcritureLet' => $e->lettrage ?? '',
                'Devise' => 'EUR',
                'Lettrage' => $e->lettrage ?? '',
            ];
        }

        return ['header' => $header, 'rows' => $rows];
    }
}
