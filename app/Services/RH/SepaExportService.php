<?php

namespace App\Services\RH;

use App\Models\Banque\BankAccount;
use App\Models\RH\ExpenseAdvance;
use App\Models\RH\ExpenseReport;
use Digitick\Sepa\TransferFile\Factory\TransferFileFacadeFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class SepaExportService
{
    /**
     * @param  Collection<ExpenseReport>  $expenseReports
     * @return string XML content
     *
     * @throws \Exception
     */
    public function generateForExpenseReports(Collection $expenseReports): string
    {
        $companyAccount = BankAccount::first();

        if (! $companyAccount || empty($companyAccount->iban) || empty($companyAccount->bic)) {
            throw new \Exception("Le compte en banque principal de l'entreprise (ou son IBAN/BIC) n'est pas configuré.");
        }

        $msgId = 'NDF-'.date('YmdHis').'-'.Str::random(4);
        $companyName = $companyAccount->company->legal_name ?? 'Entreprise';

        $transfer = TransferFileFacadeFactory::createCustomerCredit(
            $msgId,
            $companyName,
            'pain.001.001.03'
        );

        $paymentInfoId = 'PMT-'.date('YmdHis');

        $transfer->addPaymentInfo($paymentInfoId, [
            'id' => $paymentInfoId,
            'debtorName' => $companyName,
            'debtorAccountIBAN' => $companyAccount->iban,
            'debtorAgentBIC' => $companyAccount->bic,
        ]);

        foreach ($expenseReports as $report) {
            $employee = $report->employee;

            if (! $employee) {
                continue;
            }

            if (empty($employee->iban) || empty($employee->bic)) {
                throw new \Exception("L'employé {$employee->first_name} {$employee->last_name} n'a pas d'IBAN ou de BIC renseigné sur sa fiche.");
            }

            $amountToPay = $report->amount_to_pay;
            if ($amountToPay <= 0) {
                continue;
            }

            $amountInCents = (int) round($amountToPay * 100);

            $transfer->addTransfer($paymentInfoId, [
                'amount' => $amountInCents,
                'creditorName' => $employee->first_name.' '.$employee->last_name,
                'creditorIban' => $employee->iban,
                'creditorBic' => $employee->bic,
                'remittanceInformation' => "Remboursement Note de frais {$report->month}/{$report->year}",
            ]);
        }

        return $transfer->asXML();
    }

    /**
     * @param  Collection<ExpenseAdvance>  $advances
     * @return string XML content
     *
     * @throws \Exception
     */
    public function generateForExpenseAdvances(Collection $advances): string
    {
        $companyAccount = BankAccount::first();

        if (! $companyAccount || empty($companyAccount->iban) || empty($companyAccount->bic)) {
            throw new \Exception("Le compte en banque principal de l'entreprise (ou son IBAN/BIC) n'est pas configuré.");
        }

        $msgId = 'ADV-'.date('YmdHis').'-'.Str::random(4);
        $companyName = $companyAccount->company->legal_name ?? 'Entreprise';

        $transfer = TransferFileFacadeFactory::createCustomerCredit(
            $msgId,
            $companyName,
            'pain.001.001.03'
        );

        $paymentInfoId = 'PMT-ADV-'.date('YmdHis');

        $transfer->addPaymentInfo($paymentInfoId, [
            'id' => $paymentInfoId,
            'debtorName' => $companyName,
            'debtorAccountIBAN' => $companyAccount->iban,
            'debtorAgentBIC' => $companyAccount->bic,
        ]);

        foreach ($advances as $advance) {
            $employee = $advance->employee;

            if (! $employee) {
                continue;
            }

            if (empty($employee->iban) || empty($employee->bic)) {
                throw new \Exception("L'employé {$employee->first_name} {$employee->last_name} n'a pas d'IBAN ou de BIC renseigné sur sa fiche.");
            }
            if ($advance->amount <= 0) {
                continue;
            }

            $amountInCents = (int) round($advance->amount * 100);

            $transfer->addTransfer($paymentInfoId, [
                'amount' => $amountInCents,
                'creditorName' => $employee->first_name.' '.$employee->last_name,
                'creditorIban' => $employee->iban,
                'creditorBic' => $employee->bic,
                'remittanceInformation' => "Avance sur frais (Réf: {$advance->id}) - {$advance->reason}",
            ]);
        }

        return $transfer->asXML();
    }
}
