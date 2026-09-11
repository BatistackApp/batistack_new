<?php

namespace App\Services\Laser;

use App\Enums\Laser\InvoiceStatus;
use App\Enums\Laser\OrderStatus;
use App\Models\Laser\LaserCreditNote;
use App\Models\Laser\LaserInvoice;
use App\Models\Laser\LaserOrder;
use App\Support\ReferenceGenerator;
use Exception;
use Illuminate\Support\Facades\DB;

class LaserInvoiceService
{
    public function generateInvoiceReference(): string
    {
        return ReferenceGenerator::next(config('laser.invoice_prefix', 'LFAC'));
    }

    public function generateCreditNoteReference(): string
    {
        return ReferenceGenerator::next(config('laser.credit_note_prefix', 'LAVO'));
    }

    public function createInvoice(LaserOrder $order): LaserInvoice
    {
        return DB::transaction(function () use ($order) {
            $order = LaserOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $order->load('lines');

            $linesToInvoice = $order->lines->filter(
                fn ($line) => $line->delivered_quantity > $line->invoiced_quantity
            );

            if ($linesToInvoice->isEmpty()) {
                throw new Exception('Aucune ligne à facturer pour cette commande.');
            }

            $invoice = LaserInvoice::create([
                'client_id' => $order->client_id,
                'laser_order_id' => $order->id,
                'reference' => $this->generateInvoiceReference(),
                'status' => InvoiceStatus::DRAFT,
                'due_date' => now()->addDays(30),
            ]);

            $tvaRate = config('laser.vat_rate', 20);

            foreach ($linesToInvoice as $line) {
                $qtyToInvoice = $line->delivered_quantity - $line->invoiced_quantity;
                $unitPrice = $line->unit_price_ht;
                $discountPct = $line->discount_pct;
                $lineTotal = $unitPrice * $qtyToInvoice * (1 - $discountPct / 100);

                $invoice->lines()->create([
                    'laser_order_line_id' => $line->id,
                    'material_id' => $line->material_id,
                    'description' => $line->description,
                    'length_mm' => $line->length_mm,
                    'width_mm' => $line->width_mm,
                    'thickness_mm' => $line->thickness_mm,
                    'quantity' => $line->quantity,
                    'quantity_invoiced' => $qtyToInvoice,
                    'unit_price_ht' => $unitPrice,
                    'discount_pct' => $discountPct,
                    'total_ht' => round($lineTotal, 2),
                    'weight_kg' => $line->weight_kg,
                    'density_kg_m3' => $line->density_kg_m3,
                ]);

                $line->increment('invoiced_quantity', $qtyToInvoice);
            }

            $totalHt = $invoice->fresh()->lines->sum('total_ht');
            $totalTva = round($totalHt * $tvaRate / 100, 2);
            $totalTtc = $totalHt + $totalTva;

            $invoice->update([
                'total_ht' => $totalHt,
                'total_tva' => $totalTva,
                'total_ttc' => $totalTtc,
            ]);

            return $invoice;
        });
    }

    public function legalizeInvoice(LaserInvoice $invoice): void
    {
        if ($invoice->status !== InvoiceStatus::DRAFT) {
            throw new Exception('Seule une facture en brouillon peut être légalisée.');
        }

        DB::transaction(function () use ($invoice) {
            $definitiveRef = $this->generateInvoiceReference();

            $lastValidated = LaserInvoice::whereYear('created_at', now()->year)
                ->where('status', '!=', InvoiceStatus::DRAFT)
                ->whereNotNull('signature_hash')
                ->orderBy('reference', 'desc')
                ->first();

            $previousHash = $lastValidated?->signature_hash ?? 'GENESIS';

            $dataToHash = implode('|', [
                $definitiveRef,
                $invoice->created_at->toIso8601String(),
                number_format($invoice->total_ht, 2, '.', ''),
                number_format($invoice->total_ttc, 2, '.', ''),
                $invoice->client_id,
                $invoice->laser_order_id,
            ]).'|'.$previousHash;

            $newHash = hash('sha256', $dataToHash);

            $invoice->updateQuietly([
                'reference' => $definitiveRef,
                'status' => InvoiceStatus::VALIDATED,
                'signature_hash' => $newHash,
            ]);

            $invoice->order->update(['status' => OrderStatus::BILLED]);
        });
    }

    public function createCreditNote(LaserInvoice $invoice, string $reason): LaserCreditNote
    {
        if (! in_array($invoice->status, [InvoiceStatus::VALIDATED, InvoiceStatus::PAID])) {
            throw new Exception('Seules les factures validées ou payées peuvent faire l\'objet d\'un avoir.');
        }

        return LaserCreditNote::create([
            'client_id' => $invoice->client_id,
            'laser_invoice_id' => $invoice->id,
            'reference' => $this->generateCreditNoteReference(),
            'status' => 'validated',
            'total_ht' => $invoice->total_ht,
            'total_tva' => $invoice->total_tva,
            'total_ttc' => $invoice->total_ttc,
            'reason' => $reason,
        ]);
    }
}
