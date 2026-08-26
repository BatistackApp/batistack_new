<?php

namespace App\Services\RH;

use App\Models\RH\ExpenseItem;

class ExpenseValidationService
{
    /**
     * Define the maximum reimbursement limit for each category.
     * In a real application, this could be stored in a database table or Settings.
     */
    private const CATEGORY_LIMITS = [
        'Repas' => 20.20, // Convention collective BTP (exemple)
        'Hébergement' => 90.00,
        'Carburant' => null, // No strict limit, checked by anomaly detection
        'Péage' => null,
    ];

    /**
     * Validate an expense item against company policy.
     *
     * @return array{is_valid: bool, reason: ?string}
     */
    public function validateItem(ExpenseItem $item): array
    {
        // 1. Check if the category has a limit
        $limit = self::CATEGORY_LIMITS[$item->category] ?? null;

        if ($limit !== null && $item->amount_ttc > $limit) {
            return [
                'is_valid' => false,
                'reason' => "Le montant dépasse le plafond autorisé pour la catégorie {$item->category} ({$limit}€).",
            ];
        }

        // 2. Check VAT consistency if HT and VAT are provided
        if ($item->amount_ht !== null && $item->vat_amount !== null) {
            $calculatedTtc = round($item->amount_ht + $item->vat_amount, 2);
            $actualTtc = round($item->amount_ttc, 2);

            // Allow 0.05 margin of error for rounding
            if (abs($calculatedTtc - $actualTtc) > 0.05) {
                return [
                    'is_valid' => false,
                    'reason' => 'Incohérence détectée entre le montant HT, la TVA et le montant TTC.',
                ];
            }
        }

        // 3. Prevent future dates
        if ($item->date && $item->date->isFuture()) {
            return [
                'is_valid' => false,
                'reason' => 'La date de la dépense ne peut pas être dans le futur.',
            ];
        }

        return [
            'is_valid' => true,
            'reason' => null,
        ];
    }
}
