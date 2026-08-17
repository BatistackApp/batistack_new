<?php

namespace App\Services\Locations;

use App\Enums\Locations\InternalRentalInvoiceStatus;
use App\Enums\Locations\RentalBillingPeriod;
use App\Models\Immobilisation\FixedAsset;
use App\Models\Locations\InternalRentalInvoice;
use Carbon\Carbon;
use Illuminate\Database\QueryException;

/**
 * Facturation interne (refacturation) des immobilisations affectées à un chantier.
 * Impute le coût d'usage de l'actif au budget du chantier sur une base périodique.
 */
class InternalRentalBillingService
{
    /**
     * Génère la facture interne de la période courante (ou de référence) pour un actif.
     *
     * @return InternalRentalInvoice|null Null si non facturable ou déjà facturé (anti-doublon).
     */
    public function generateForAsset(FixedAsset $fixedAsset, ?Carbon $reference = null): ?InternalRentalInvoice
    {
        $reference = $reference ?? Carbon::now();

        if (! $fixedAsset->chantier_id || ! $fixedAsset->daily_rate) {
            return null;
        }

        $period = $fixedAsset->internal_rental_period ?? RentalBillingPeriod::MONTHLY;
        [$periodStart, $periodEnd, $periodKey] = $this->resolvePeriod($period, $reference);

        // L'identité de facturation inclut le chantier : une ré-affectation en
        // cours de période génère une nouvelle facture pour le nouveau chantier
        // au lieu de réutiliser celle facturée pour le chantier précédent.
        $billingKey = sprintf('INT-%s-%s-%s', $fixedAsset->id, $fixedAsset->chantier_id, $periodKey);

        $days = $periodStart->copy()->startOfDay()->diffInDays($periodEnd->copy()->addDay()->startOfDay());
        $amount = round($days * (float) $fixedAsset->daily_rate, 2);

        $attributes = [
            'chantier_id' => $fixedAsset->chantier_id,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'days' => $days,
            'daily_rate' => $fixedAsset->daily_rate,
            'amount_ht' => $amount,
            'status' => InternalRentalInvoiceStatus::DRAFT,
        ];

        $existing = $fixedAsset->internalRentalInvoices()
            ->where('billing_key', $billingKey)
            ->first();

        if ($existing) {
            // Une facture annulée est réémise (réactivée) pour la période courante.
            if ($existing->status === InternalRentalInvoiceStatus::CANCELED) {
                $existing->update($attributes);
            }

            return $existing;
        }

        try {
            return $fixedAsset->internalRentalInvoices()->create($attributes + ['billing_key' => $billingKey]);
        } catch (QueryException) {
            // Course concurrente : la contrainte unique `billing_key` a déjà été
            // satisfaite, on renvoie la facture insérée par l'autre requête.
            return $fixedAsset->internalRentalInvoices()
                ->where('billing_key', $billingKey)
                ->first();
        }
    }

    /**
     * Génère les factures internes dues pour tous les actifs affectés à un chantier.
     *
     * @return array<int, InternalRentalInvoice>
     */
    public function generateDueInvoices(?Carbon $reference = null): array
    {
        $invoices = [];

        $assets = FixedAsset::query()
            ->whereNotNull('chantier_id')
            ->whereNotNull('daily_rate')
            ->where('daily_rate', '>', 0)
            ->get();

        foreach ($assets as $asset) {
            $invoice = $this->generateForAsset($asset, $reference);
            if ($invoice && $invoice->wasRecentlyCreated) {
                $invoices[] = $invoice;
            }
        }

        return $invoices;
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    protected function resolvePeriod(RentalBillingPeriod $period, Carbon $reference): array
    {
        return match ($period) {
            RentalBillingPeriod::DAILY => [
                $reference->copy()->startOfDay(),
                $reference->copy()->endOfDay(),
                $reference->format('Ymd'),
            ],
            RentalBillingPeriod::WEEKLY => [
                $reference->copy()->startOfWeek(),
                $reference->copy()->endOfWeek(),
                $reference->isoWeekYear().'-W'.$reference->isoWeek(),
            ],
            RentalBillingPeriod::MONTHLY => [
                $reference->copy()->startOfMonth(),
                $reference->copy()->endOfMonth(),
                $reference->format('Ym'),
            ],
            RentalBillingPeriod::YEARLY => [
                $reference->copy()->startOfYear(),
                $reference->copy()->endOfYear(),
                $reference->format('Y'),
            ],
        };
    }
}
