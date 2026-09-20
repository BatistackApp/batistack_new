<?php

namespace App\Services\Laser;

use App\Enums\Laser\OrderStatus;
use App\Enums\Laser\QuoteStatus;
use App\Enums\Core\SignatureStatus;
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

    /**
     * @deprecated Use convertToOrder(). Kept as a strict alias for callers being migrated.
     */
    public function acceptQuote(LaserQuote $quote): LaserOrder
    {
        return $this->convertToOrder($quote);
    }

    public function convertToOrder(LaserQuote $quote): LaserOrder
    {
        return DB::transaction(function () use ($quote) {
            $quote = LaserQuote::whereKey($quote->id)->lockForUpdate()->firstOrFail();

            if ($quote->order()->exists()) {
                throw new Exception('Ce devis a déjà été converti en commande.');
            }

            if ($quote->status !== QuoteStatus::ACCEPTED) {
                throw new Exception('Seul un devis accepté et signé peut être converti en commande.');
            }

            $signature = $quote->signatures()
                ->where('status', SignatureStatus::SIGNED)
                ->whereNotNull('signed_at')
                ->latest('signed_at')
                ->first();

            if (! $signature || ! $quote->signed_at) {
                throw new Exception('Une signature électronique valide est obligatoire avant la conversion en commande.');
            }

            return $this->createOrderFromQuote($quote);
        });
    }

    private function createOrderFromQuote(LaserQuote $quote): LaserOrder
    {
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
                'dxf_entities' => $line->dxf_entities,
            ]);

            if ($linesToCreate->isNotEmpty()) {
                $order->lines()->createMany($linesToCreate->all());
            }

            return $order;
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
