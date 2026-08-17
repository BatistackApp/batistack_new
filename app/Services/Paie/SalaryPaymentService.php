<?php

namespace App\Services\Paie;

use App\Enums\Paie\PayslipStatus;
use App\Enums\Paie\SalaryPaymentStatus;
use App\Models\Banque\BankAccount;
use App\Models\Paie\Payslip;
use App\Models\Paie\SalaryPaymentLine;
use App\Models\Paie\SalaryPaymentRun;
use App\Models\User;
use App\Services\Banque\BridgePaymentService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Orchestration du paiement des salaires par virement API (Bridge Payment Initiation).
 *
 * Un run de paiement = une requête de paiement Bridge (bulk) regroupant un virement
 * par bulletin. Le payeur valide ensuite le consent URL retourné par Bridge à sa banque.
 */
class SalaryPaymentService
{
    public function __construct(private BridgePaymentService $bridge) {}

    /**
     * Crée un run de paiement (bulk) à partir des bulletins sélectionnés.
     *
     * @throws \RuntimeException Si un bulletin n'est pas payant ou qu'un IBAN bénéficiaire/émetteur manque.
     */
    public function createRun(Collection $payslips, BankAccount $source, User $creator): SalaryPaymentRun
    {
        if (! $source->bridge_bank_id) {
            throw new \RuntimeException("Le compte émetteur n'est pas relié à une banque Bridge (bridge_bank_id manquant).");
        }

        $validated = $payslips->filter(fn (Payslip $p) => $p->net_paid > 0);

        if ($validated->isEmpty()) {
            throw new \RuntimeException('Aucun bulletin payant sélectionné.');
        }

        $lines = $validated->map(function (Payslip $payslip) {
            if (! $payslip->employee?->iban) {
                throw new \RuntimeException("IBAN manquant pour l'employé {$payslip->employee?->getFullName()}");
            }

            return [
                'payslip_id' => $payslip->id,
                'employee_id' => $payslip->employee_id,
                'amount' => $payslip->net_paid,
                'status' => SalaryPaymentStatus::PENDING,
                'end_to_end_id' => "SAL-{$payslip->id}",
            ];
        });

        $total = $lines->sum('amount');
        $period = $validated->first()->period;
        $idempotencyKey = $this->buildIdempotencyKey($source, $period, $lines);

        return DB::transaction(function () use ($source, $creator, $period, $total, $lines, $idempotencyKey) {
            $existing = SalaryPaymentRun::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }

            $run = SalaryPaymentRun::create([
                'company_id' => $source->company_id,
                'bank_account_id' => $source->id,
                'period' => $period,
                'total_amount' => $total,
                'count' => $lines->count(),
                'status' => SalaryPaymentStatus::PENDING,
                'idempotency_key' => $idempotencyKey,
                'created_by' => $creator->id,
            ]);

            foreach ($lines as $line) {
                $run->lines()->create($line);
            }

            return $run;
        });
    }

    /**
     * Envoie la requête de paiement à Bridge et stocke le consent URL.
     */
    public function initiateRun(SalaryPaymentRun $run): void
    {
        if ($run->status !== SalaryPaymentStatus::PENDING) {
            return;
        }

        $source = $run->bankAccount;

        $transactions = $run->lines->map(function (SalaryPaymentLine $line) use ($run) {
            $employee = $line->employee;

            return [
                'amount' => (float) $line->amount,
                'currency' => 'EUR',
                'label' => 'Salaire '.$run->period,
                'beneficiary' => [
                    'first_name' => $employee?->first_name ?? '',
                    'last_name' => $employee?->last_name ?? '',
                    'iban' => $employee?->iban ?? '',
                ],
                'client_reference' => (string) $line->id,
                'end_to_end_id' => $line->end_to_end_id,
            ];
        })->values()->all();

        $result = $this->bridge->initiatePaymentRequest(
            transactions: $transactions,
            user: [
                'first_name' => $run->creator?->name ?? 'ERP',
                'last_name' => '',
                'external_reference' => 'company_'.$source->company_id,
            ],
            providerId: (string) $source->bridge_bank_id,
            clientReference: $run->idempotency_key,
        );

        $run->update([
            'bridge_payment_request_id' => $result['id'],
            'consent_url' => $result['url'],
            'status' => SalaryPaymentStatus::AWAITING_VALIDATION,
        ]);

        $run->lines()->update(['status' => SalaryPaymentStatus::AWAITING_VALIDATION]);
    }

    /**
     * Interroge Bridge et applique le statut final de la requête de paiement.
     */
    public function pollRun(SalaryPaymentRun $run): void
    {
        if (! $run->bridge_payment_request_id) {
            return;
        }

        $bridgeStatus = $this->bridge->getPaymentRequestStatus($run->bridge_payment_request_id);
        $status = $this->bridge->mapStatus($bridgeStatus);

        $run->update(['status' => $status]);

        match ($status) {
            SalaryPaymentStatus::SUCCEEDED => $this->markRunSucceeded($run),
            SalaryPaymentStatus::FAILED, SalaryPaymentStatus::CANCELED => $run->lines()->update(['status' => $status]),
            default => $run->lines()->update(['status' => $status]),
        };
    }

    private function markRunSucceeded(SalaryPaymentRun $run): void
    {
        DB::transaction(function () use ($run) {
            foreach ($run->lines as $line) {
                $line->update([
                    'status' => SalaryPaymentStatus::SUCCEEDED,
                    'bank_reference' => $run->bridge_payment_request_id,
                ]);

                $line->payslip?->update([
                    'status' => PayslipStatus::PAID,
                    'payment_date' => now()->toDateString(),
                ]);
            }
        });
    }

    private function buildIdempotencyKey(BankAccount $source, string $period, Collection $lines): string
    {
        $signature = $lines
            ->map(fn ($l) => "{$l['payslip_id']}:{$l['amount']}")
            ->sort()
            ->implode('|');

        return hash('sha256', "{$source->id}:{$period}:{$signature}");
    }
}
