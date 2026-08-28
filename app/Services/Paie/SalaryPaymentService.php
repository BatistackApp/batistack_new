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
     * @throws \RuntimeException Si un bulletin n'est pas éligible ou qu'un IBAN bénéficiaire/émetteur manque.
     */
    public function createRun(Collection $payslips, BankAccount $source, User $creator): SalaryPaymentRun
    {
        if (! $source->bridge_bank_id) {
            throw new \RuntimeException("Le compte �metteur n'est pas reli� � une banque Bridge (bridge_bank_id manquant).");
        }

        if ($source->currency !== 'EUR') {
            throw new \RuntimeException("Le compte �metteur doit �tre en devise EUR pour un paiement Bridge (actuel : {$source->currency}).");
        }

        $eligible = $payslips->filter(
            fn (Payslip $p) => $p->net_paid > 0 && $p->status === PayslipStatus::VALIDATED
        );

        if ($eligible->isEmpty()) {
            throw new \RuntimeException('Aucun bulletin payant sélectionné.');
        }

        $periods = $eligible->pluck('period')->unique();
        if ($periods->count() > 1) {
            throw new \RuntimeException('Les bulletins sélectionnés doivent appartenir à la même période de paie.');
        }

        $lines = $eligible->map(function (Payslip $payslip) {
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
        $period = $periods->first();
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
     *
     * La sortie de PENDING est réservée de façon atomique afin qu'une exécution
     * concurrente ne puisse pas réinitier le même run.
     *
     * @return bool true si une nouvelle demande Bridge a été créée.
     */
    public function initiateRun(SalaryPaymentRun $run): bool
    {
        $reserved = DB::transaction(function () use ($run) {
            $locked = SalaryPaymentRun::whereKey($run->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== SalaryPaymentStatus::PENDING) {
                return false;
            }

            // Transition immédiate hors de PENDING : bloque toute réinitiation concurrente.
            $locked->update(['status' => SalaryPaymentStatus::PROCESSING]);

            return true;
        });

        if (! $reserved) {
            return false;
        }

        $run->refresh();

        // Réconciliation : si une demande Bridge existe déjà (créée lors d'une
        // tentative précédente avant une erreur de transport), on ne crée pas
        // de nouvelle demande et on rétablit l'état en attente de validation.
        if ($run->bridge_payment_request_id) {
            $this->applyInitiationResult($run, $run->bridge_payment_request_id, $run->consent_url);

            return false;
        }

        $source = $run->bankAccount;
        $result = $this->bridge->initiatePaymentRequest(
            transactions: $this->buildTransactions($run),
            user: $this->buildUser($run),
            providerId: (string) $source->bridge_bank_id,
            clientReference: $run->idempotency_key,
        );

        $this->applyInitiationResult($run, $result['id'], $result['url']);

        return true;
    }

    /**
     * Régénère une demande Bridge pour un run déjà en attente de validation
     * (lien de consentement expiré). Réservé au flux de réinitiation explicite.
     */
    public function reinitiateRun(SalaryPaymentRun $run): bool
    {
        if ($run->status !== SalaryPaymentStatus::AWAITING_VALIDATION) {
            throw new \RuntimeException('Le run doit être en attente de validation pour être réinitié.');
        }

        $source = $run->bankAccount;
        $result = $this->bridge->initiatePaymentRequest(
            transactions: $this->buildTransactions($run),
            user: $this->buildUser($run),
            providerId: (string) $source->bridge_bank_id,
            clientReference: $run->idempotency_key,
        );

        $this->applyInitiationResult($run, $result['id'], $result['url']);

        return true;
    }

    /**
     * Interroge Bridge et applique le statut final de la requête de paiement.
     */
    public function pollRun(SalaryPaymentRun $run): void
    {
        if (! $run->bridge_payment_request_id) {
            return;
        }

        $status = $this->bridge->mapStatus($this->bridge->getPaymentRequestStatus($run->bridge_payment_request_id));

        $run->update(['status' => $status]);

        if ($status === SalaryPaymentStatus::SUCCEEDED) {
            $this->markRunSucceeded($run);

            return;
        }

        // Mise à jour ligne par ligne pour déclencher les événements et l'audit.
        foreach ($run->lines as $line) {
            $line->update(['status' => $status]);
        }
    }

    private function buildTransactions(SalaryPaymentRun $run): array
    {
        return $run->lines->map(function (SalaryPaymentLine $line) use ($run) {
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
    }

    private function buildUser(SalaryPaymentRun $run): array
    {
        return [
            'first_name' => $run->creator?->name ?? 'ERP',
            'last_name' => '',
            'external_reference' => 'company_'.$run->bankAccount->company_id,
        ];
    }

    private function applyInitiationResult(SalaryPaymentRun $run, string $bridgePaymentRequestId, ?string $consentUrl): void
    {
        $run->update([
            'bridge_payment_request_id' => $bridgePaymentRequestId,
            'consent_url' => $consentUrl,
            'status' => SalaryPaymentStatus::AWAITING_VALIDATION,
        ]);

        $run->lines()->update(['status' => SalaryPaymentStatus::AWAITING_VALIDATION]);
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
