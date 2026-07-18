<?php

namespace App\Services\Paie;

use App\Models\Core\Company;
use Digitick\Sepa\TransferFile\Factory\TransferFileFacadeFactory;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class SepaExportService
{
    /**
     * Genere le fichier XML SEPA (pain.001.001.03) pour une collection de bulletins.
     *
     * @param Collection $payslips
     * @return string Le contenu XML
     * @throws Exception Si les coordonnées bancaires de l'entreprise ou d'un employé sont manquantes.
     */
    public function generateXml(Collection $payslips): string
    {
        $company = Company::first();

        if (!$company || empty($company->iban)) {
            throw new Exception("L'entreprise n'a pas d'IBAN configuré dans les paramètres système.");
        }

        // Nettoyer l'IBAN et le BIC de l'entreprise
        $companyIban = str_replace(' ', '', $company->iban);
        $companyBic = $company->bic ? str_replace(' ', '', $company->bic) : null;
        $companyName = $company->legal_name ?? 'Entreprise Inconnue';

        // Identifiants de messages SEPA
        $messageId = 'MSG-' . date('YmdHis');
        $paymentInfoId = 'PMT-' . date('YmdHis');

        $transferFile = TransferFileFacadeFactory::createCustomerCredit($messageId, $companyName, 'pain.001.001.03');

        $paymentInfo = [
            'id' => $paymentInfoId,
            'debtorName' => $companyName,
            'debtorAccountIBAN' => $companyIban,
        ];

        if ($companyBic) {
            $paymentInfo['debtorAgentBIC'] = $companyBic;
        }

        $transferFile->addPaymentInfo($paymentInfoId, $paymentInfo);

        foreach ($payslips as $payslip) {
            // Ignorer les bulletins avec net à payer <= 0
            if ($payslip->net_paid <= 0) {
                continue;
            }

            $employee = $payslip->employee;
            if (!$employee || empty($employee->iban)) {
                throw new Exception("Le salarié {$employee->getFullName()} n'a pas d'IBAN configuré.");
            }

            $employeeIban = str_replace(' ', '', $employee->iban);
            $employeeBic = $employee->bic ? str_replace(' ', '', $employee->bic) : null;
            $remittanceInfo = 'Salaire ' . $payslip->period;

            $transfer = [
                'amount' => (int) round($payslip->net_paid * 100), // En centimes pour digitick
                'creditorName' => $employee->getFullName(),
                'creditorIban' => $employeeIban,
                'remittanceInformation' => $remittanceInfo,
            ];

            if ($employeeBic) {
                $transfer['creditorBic'] = $employeeBic;
            }

            $transferFile->addTransfer($paymentInfoId, $transfer);
        }

        return $transferFile->asXML();
    }
}
