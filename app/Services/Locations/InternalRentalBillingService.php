<?php

namespace App\Services\Locations;

use App\Enums\Locations\InternalRentalInvoiceStatus;
use App\Enums\Locations\RentalBillingPeriod;
use App\Models\Immobilisation\FixedAsset;
use App\Models\Locations\InternalRentalInvoice;
use Carbon\Carbon;

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

        $billingKey = sprintf('INT-%s-%s', $fixedAsset->id, $periodKey);

        $existing = $this->existingInvoice($fixedAsset, $billingKey);
        if ($existing) {
            return $existing;
        }

        $days = $periodStart->copy()->startOfDay()->diffInDays($periodEnd->copy()->addDay()->startOfDay());
        $amount = round($days * (float) $fixedAsset->daily_rate, 2);

        return $fixedAsset->internalRentalInvoices()->create([
            'chantier_id' => $fixedAsset->chantier_id,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'days' => $days,
            'daily_rate' => $fixedAsset->daily_rate,
            'amount_ht' => $amount,
            'status' => InternalRentalInvoiceStatus::DRAFT,
            'billing_key' => $billingKey,
        ]);
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

    protected function existingInvoice(FixedAsset $fixedAsset, string $billingKey): ?InternalRentalInvoice
    {
        return $fixedAsset->internalRentalInvoices()
            ->where('billing_key', $billingKey)
            ->where('status', '!=', InternalRentalInvoiceStatus::CANCELED->value)
            ->first();
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
