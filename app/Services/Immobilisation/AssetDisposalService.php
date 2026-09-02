<?php

namespace App\Services\Immobilisation;

use App\Enums\Accounting\JournalType;
use App\Enums\Immobilisation\AssetStatus;
use App\Models\Immobilisation\AssetDisposal;
use App\Models\Immobilisation\FixedAsset;
use App\Services\Accounting\EcritureComptableService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AssetDisposalService
{
    public function __construct(
        protected EcritureComptableService $ecritureService,
    ) {}

    public function dispose(FixedAsset $asset, string $disposalDate, float $salePrice, string $reason): AssetDisposal
    {
        return DB::transaction(function () use ($asset, $disposalDate, $salePrice, $reason) {
            $lastPassedDepreciation = $asset->depreciations()
                ->where('is_passed', true)
                ->orderByDesc('period_date')
                ->first();

            $currentVnc = $lastPassedDepreciation
                ? $lastPassedDepreciation->remaining_vnc
                : ($asset->purchase_price - $asset->salvage_value);

            $totalDepreciated = $asset->purchase_price - $asset->salvage_value - $currentVnc;
            $profitOrLoss = $salePrice - $currentVnc;

            $disposal = AssetDisposal::create([
                'fixed_asset_id' => $asset->id,
                'disposal_date' => Carbon::parse($disposalDate),
                'sale_price' => $salePrice,
                'reason' => $reason,
                'profit_or_loss' => $profitOrLoss,
            ]);

            $asset->update(['status' => AssetStatus::DISPOSED]);

            $asset->depreciations()
                ->where('is_passed', false)
                ->delete();

            $this->createAccountingEntries($asset, $disposal, $totalDepreciated, $profitOrLoss);

            return $disposal;
        });
    }

    protected function createAccountingEntries(
        FixedAsset $asset,
        AssetDisposal $disposal,
        float $totalDepreciated,
        float $profitOrLoss,
    ): void {
        $date = $disposal->disposal_date->format('Y-m-d');
        $numeroPiece = $this->ecritureService->generateNumeroPiece(JournalType::OD);
        $libelle = "Cession immobilisation: {$asset->name}";

        $category = $asset->category;

        // 1. Credit the asset account (remove from balance sheet)
        $this->ecritureService->createEntry([
            'date_ecriture' => $date,
            'date_piece' => $date,
            'journal_type' => JournalType::OD,
            'numero_piece' => $numeroPiece,
            'compte_numero' => $category->account_code,
            'libelle' => $libelle,
            'debit' => 0,
            'credit' => $asset->purchase_price,
        ]);

        // 2. Debit the depreciation account (remove accumulated depreciation)
        if ($totalDepreciated > 0) {
            $this->ecritureService->createEntry([
                'date_ecriture' => $date,
                'date_piece' => $date,
                'journal_type' => JournalType::OD,
                'numero_piece' => $numeroPiece,
                'compte_numero' => $category->compte_amortissement,
                'libelle' => $libelle,
                'debit' => $totalDepreciated,
                'credit' => 0,
            ]);
        }

        // 3. Debit the bank account (sale price received)
        if ($disposal->sale_price > 0) {
            $this->ecritureService->createEntry([
                'date_ecriture' => $date,
                'date_piece' => $date,
                'journal_type' => JournalType::OD,
                'numero_piece' => $numeroPiece,
                'compte_numero' => '512000',
                'libelle' => $libelle,
                'debit' => $disposal->sale_price,
                'credit' => 0,
            ]);
        }

        // 4. Record gain or loss
        if ($profitOrLoss > 0) {
            // Gain: credit 754000 (Produits des cessions)
            $this->ecritureService->createEntry([
                'date_ecriture' => $date,
                'date_piece' => $date,
                'journal_type' => JournalType::OD,
                'numero_piece' => $numeroPiece,
                'compte_numero' => '754000',
                'libelle' => $libelle,
                'debit' => 0,
                'credit' => $profitOrLoss,
            ]);
        } elseif ($profitOrLoss < 0) {
            // Loss: debit 654000 (Pertes sur cessions)
            $this->ecritureService->createEntry([
                'date_ecriture' => $date,
                'date_piece' => $date,
                'journal_type' => JournalType::OD,
                'numero_piece' => $numeroPiece,
                'compte_numero' => '654000',
                'libelle' => $libelle,
                'debit' => abs($profitOrLoss),
                'credit' => 0,
            ]);
        }
    }
}
