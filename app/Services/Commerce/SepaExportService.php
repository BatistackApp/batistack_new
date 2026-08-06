<?php

namespace App\Services\Commerce;

use App\Models\Banque\BankAccount;
use App\Models\Commerce\SupplierInvoice;
use Digitick\Sepa\TransferFile\Factory\TransferFileFacadeFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class SepaExportService
{
    /**
     * Genere le fichier XML SEPA (pain.001.001.03) pour une collection de factures fournisseurs.
     *
     * @param Collection<SupplierInvoice> $invoices
     * @return string XML content
     * @throws \Exception
     */
    public function generateForSupplierInvoices(Collection $invoices): string
    {
        $companyAccount = BankAccount::first();

        if (!$companyAccount || empty($companyAccount->iban) || empty($companyAccount->bic)) {
            throw new \Exception("Le compte en banque principal de l'entreprise (ou son IBAN/BIC) n'est pas configuré.");
        }

        $msgId = 'SUP-' . date('YmdHis') . '-' . Str::random(4);
        $companyName = $companyAccount->company->legal_name ?? 'Entreprise';
        
        $transfer = TransferFileFacadeFactory::createCustomerCredit(
            $msgId, 
            $companyName, 
            'pain.001.001.03'
        );

        $paymentInfoId = 'PMT-SUP-' . date('YmdHis');

        $transfer->addPaymentInfo($paymentInfoId, [
            'id' => $paymentInfoId,
            'debtorName' => $companyName,
            'debtorAccountIBAN' => $companyAccount->iban,
            'debtorAgentBIC' => $companyAccount->bic,
        ]);

        foreach ($invoices as $invoice) {
            $supplier = $invoice->supplier;

            if (!$supplier) {
                continue;
            }

            if (empty($supplier->iban) || empty($supplier->bic)) {
                throw new \Exception("Le fournisseur {$supplier->name} n'a pas d'IBAN ou de BIC renseigné sur sa fiche.");
            }

            $amountToPay = $invoice->amount_remaining;
            
            if ($amountToPay <= 0) {
                continue;
            }

            $amountInCents = (int) round($amountToPay * 100);

            $transfer->addTransfer($paymentInfoId, [
                'amount' => $amountInCents,
                'creditorName' => $supplier->name,
                'creditorIban' => $supplier->iban,
                'creditorBic' => $supplier->bic,
                'remittanceInformation' => "Règlement facture {$invoice->reference}",
            ]);
        }
        
        return $transfer->asXML();
    }
}
