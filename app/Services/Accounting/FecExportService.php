<?php

namespace App\Services\Accounting;

use App\Models\Immobilisation\Depreciation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FecExportService
{
    /**
     * Génère le fichier FEC pour les amortissements d'une année donnée.
     *
     * @param int $year Année de l'exercice comptable
     * @return string Chemin absolu vers le fichier généré
     */
    public function exportDepreciationsFec(int $year): string
    {
        // Récupérer toutes les dotations validées (passées) de l'année
        $depreciations = Depreciation::with(['fixedAsset.category'])
            ->where('is_passed', true)
            ->whereYear('period_date', $year)
            ->orderBy('period_date')
            ->get();

        $lines = [];

        // 1. En-tête FEC (18 colonnes obligatoires)
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

        $lines[] = implode("\t", $header);

        $journalCode = 'OD';
        $journalLib = 'Opérations Diverses';

        foreach ($depreciations as $dep) {
            $dateStr = $dep->period_date->format('Ymd');
            // Numéro d'écriture unique basé sur l'ID de la dotation
            $ecritureNum = 'AMORT-' . $year . '-' . str_pad($dep->id, 5, '0', STR_PAD_LEFT);
            $pieceRef = 'DOT-' . $year;
            $libelle = 'Dotation amort. ' . Str::limit($dep->fixedAsset->name, 30);
            
            // Format des montants : séparateur décimal = virgule
            $montant = number_format($dep->amount, 2, ',', '');

            // --- LIGNE DE DEBIT (Compte 6811 - Dotations) ---
            $lines[] = implode("\t", [
                $journalCode,
                $journalLib,
                $ecritureNum,
                $dateStr,
                '6811', // CompteNum
                'Dotations aux amortissements', // CompteLib
                '', // CompAuxNum
                '', // CompAuxLib
                $pieceRef,
                $dateStr,
                $libelle,
                $montant, // Debit
                '0,00', // Credit
                '', // EcritureLet
                '', // DateLet
                $dateStr, // ValidDate
                '', // Montantdevise
                '', // Idevise
            ]);

            // --- LIGNE DE CREDIT (Compte 28... - Amortissements) ---
            // On déduit le compte d'amortissement à partir du code compte d'actif
            $assetAccount = $dep->fixedAsset->category->account_code ?: '2182'; // fallback par défaut
            // Insertion d'un 8 en deuxième position (ex: 2182 -> 28182)
            $amortAccount = substr_replace((string)$assetAccount, '8', 1, 0);

            $lines[] = implode("\t", [
                $journalCode,
                $journalLib,
                $ecritureNum,
                $dateStr,
                $amortAccount, // CompteNum
                'Amort. ' . Str::limit($dep->fixedAsset->category->name, 20), // CompteLib
                '', // CompAuxNum
                '', // CompAuxLib
                $pieceRef,
                $dateStr,
                $libelle,
                '0,00', // Debit
                $montant, // Credit
                '', // EcritureLet
                '', // DateLet
                $dateStr, // ValidDate
                '', // Montantdevise
                '', // Idevise
            ]);
        }

        $content = implode("\r\n", $lines);
        
        // Siren fictif pour le nom du fichier (standard FEC : SIRENFECYYYYMMDD)
        $siren = '123456789';
        $filename = $siren . 'FEC' . $year . '1231.txt';
        $relativePath = 'exports/fec/' . $filename;

        Storage::disk('local')->put($relativePath, $content);

        return Storage::disk('local')->path($relativePath);
    }
}
