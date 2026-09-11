<?php

namespace App\Services\Laser;

use App\Enums\Laser\OrderStatus;
use App\Enums\Laser\QuoteStatus;
use App\Models\Laser\LaserOrder;
use App\Models\Laser\LaserQuote;
use App\Models\Laser\LaserQuoteLine;
use App\Support\ReferenceGenerator;
use Exception;
use Illuminate\Support\Facades\DB;

class LaserQuoteService
{
    public function generateReference(): string
    {
        return ReferenceGenerator::next('LAQ');
    }

    public function generateOrderReference(): string
    {
        return ReferenceGenerator::next(config('laser.order_prefix', 'LAC'));
    }

    public function acceptQuote(LaserQuote $quote): LaserOrder
    {
        return DB::transaction(function () use ($quote) {
            $quote = LaserQuote::whereKey($quote->id)->lockForUpdate()->firstOrFail();

            if ($quote->order()->exists()) {
                throw new Exception('Ce devis a déjà été converti en commande.');
            }

            if (! in_array($quote->status, [QuoteStatus::DRAFT, QuoteStatus::SENT, QuoteStatus::ACCEPTED], true)) {
                throw new Exception('Ce devis ne peut pas être accepté dans son état actuel.');
            }

            $order = LaserOrder::create([
                'client_id' => $quote->client_id,
                'laser_quote_id' => $quote->id,
                'reference' => $this->generateOrderReference(),
                'status' => OrderStatus::CONFIRMED,
                'total_ht' => $quote->total_ht,
                'total_ttc' => $quote->total_ttc,
                'terms' => $quote->terms,
            ]);

            $quote->load('lines');
            $linesToCreate = $quote->lines->map(fn (LaserQuoteLine $line) => [
                'material_id' => $line->material_id,
                'description' => $line->description,
                'length_mm' => $line->length_mm,
                'width_mm' => $line->width_mm,
                'thickness_mm' => $line->thickness_mm,
                'quantity' => $line->quantity,
                'surface_mm2' => $line->surface_mm2,
                'cut_length_mm' => $line->cut_length_mm,
                'weight_kg' => $line->weight_kg,
                'price_per_kg' => $line->price_per_kg,
                'price_per_meter' => $line->price_per_meter,
                'programming_cost' => $line->programming_cost,
                'discount_pct' => $line->discount_pct,
                'unit_price_ht' => $line->unit_price_ht,
                'total_ht' => $line->total_ht,
                'density_kg_m3' => $line->density_kg_m3,
            ]);

            if ($linesToCreate->isNotEmpty()) {
                $order->lines()->createMany($linesToCreate->all());
            }

            if ($quote->status !== QuoteStatus::ACCEPTED) {
                $quote->update(['status' => QuoteStatus::ACCEPTED]);
            }

            return $order;
        });
    }

    public function calculateLine(LaserQuoteLine $line): void
    {
        $line->recalculate();
    }

    public function applyDiscount(int $quantity): float
    {
        if ($quantity >= 20) {
            return 15.0;
        }
        if ($quantity >= 10) {
            return 10.0;
        }
        if ($quantity >= 5) {
            return 5.0;
        }

        return 0.0;
    }

    public function calculateLineTotal(
        float $weightKg,
        float $pricePerKg,
        float $cutLengthMm,
        float $pricePerMeter,
        float $programmingCost,
        int $quantity,
        float $discountPct,
    ): float {
        $prixPoids = $weightKg * $pricePerKg;
        $prixMetre = ($cutLengthMm / 1000) * $pricePerMeter;
        $unitPrice = max($prixPoids, $prixMetre) + $programmingCost;

        return round($unitPrice * $quantity * (1 - $discountPct / 100), 2);
    }
}
