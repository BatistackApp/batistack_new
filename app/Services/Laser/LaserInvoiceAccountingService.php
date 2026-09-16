<?php

namespace App\Services\Laser;

use App\Enums\Accounting\JournalType;
use App\Enums\Laser\InvoiceStatus;
use App\Models\Accounting\AccountingSync;
use App\Models\Accounting\EcritureComptable;
use App\Models\Laser\LaserCreditNote;
use App\Models\Laser\LaserInvoice;
use App\Services\Accounting\EcritureComptableService;
use App\Services\Core\SettingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LaserInvoiceAccountingService
{
    public function __construct(
        private EcritureComptableService $ecritureService,
        private SettingService $settingService,
    ) {}

    public function syncInvoice(LaserInvoice $invoice): AccountingSync
    {
        if ($invoice->status !== InvoiceStatus::VALIDATED) {
            throw new InvalidArgumentException('Seule une facture Laser validée peut être comptabilisée.');
        }

        return $this->syncDocument($invoice, false);
    }

    public function syncCreditNote(LaserCreditNote $creditNote): AccountingSync
    {
        if ($creditNote->status !== 'validated') {
            throw new InvalidArgumentException('Seul un avoir Laser validé peut être comptabilisé.');
        }

        return $this->syncDocument($creditNote, true);
    }

    private function syncDocument(Model $document, bool $isCreditNote): AccountingSync
    {
        return DB::transaction(function () use ($document, $isCreditNote) {
            $sync = AccountingSync::query()->firstOrCreate(
                [
                    'syncable_type' => $document->getMorphClass(),
                    'syncable_id' => $document->getKey(),
                ],
                ['status' => 'pending']
            );

            if ($sync->status === 'synced') {
                return $sync;
            }

            $amountHt = (float) $document->total_ht;
            $amountTva = (float) $document->total_tva;
            $amountTtc = (float) $document->total_ttc;

            if ($amountHt <= 0 || $amountTtc <= 0 || abs($amountTtc - ($amountHt + $amountTva)) > 0.01) {
                throw new InvalidArgumentException('Les montants du document Laser sont invalides ou déséquilibrés.');
            }

            $date = $document->created_at?->toDateString() ?? now()->toDateString();
            $numeroPiece = $document->reference;
            $libelle = ($isCreditNote ? 'Avoir Laser ' : 'Facture Laser ').$document->reference;
            $clientAccount = $this->settingService->get('customer_account', '411100');
            $salesAccount = $this->settingService->get('laser_sales_account', '704000');
            $vatAccount = $this->settingService->get('laser_vat_account', '445711');

            $entries = [
                [
                    'compte_numero' => $clientAccount,
                    'debit' => $isCreditNote ? 0 : $amountTtc,
                    'credit' => $isCreditNote ? $amountTtc : 0,
                ],
                [
                    'compte_numero' => $salesAccount,
                    'debit' => $isCreditNote ? $amountHt : 0,
                    'credit' => $isCreditNote ? 0 : $amountHt,
                ],
                [
                    'compte_numero' => $vatAccount,
                    'debit' => $isCreditNote ? $amountTva : 0,
                    'credit' => $isCreditNote ? 0 : $amountTva,
                ],
            ];

            foreach ($entries as $entry) {
                EcritureComptable::create([
                    'date_ecriture' => $date,
                    'date_piece' => $date,
                    'journal_type' => JournalType::VENTES,
                    'numero_piece' => $numeroPiece,
                    'compte_numero' => $entry['compte_numero'],
                    'libelle' => $libelle,
                    'debit' => $entry['debit'],
                    'credit' => $entry['credit'],
                    'reconcilable_type' => $document->getMorphClass(),
                    'reconcilable_id' => $document->getKey(),
                ]);
            }

            $sync->update([
                'status' => 'synced',
                'last_error' => null,
                'synced_at' => now(),
            ]);

            return $sync->fresh();
        });
    }
}
