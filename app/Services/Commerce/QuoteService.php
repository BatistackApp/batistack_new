<?php

namespace App\Services\Commerce;

use App\Enums\Chantiers\ChantierStatus;
use App\Enums\Commerce\OrderStatus;
use App\Enums\Commerce\QuoteStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerQuote;
use App\Models\User;
use DB;
use Exception;

class QuoteService
{
    /**
     * Valide un devis client, génère la commande officielle et initialise le chantier.
     *
     * @throws Exception
     * @throws \Throwable
     */
    public function acceptQuote(CustomerQuote $quote, User $responsable): CustomerOrder
    {
        if ($quote->status !== QuoteStatus::SENT && $quote->status !== QuoteStatus::DRAFT) {
            throw new Exception('Ce devis ne peut pas être accepté dans son état actuel.');
        }

        return DB::transaction(function () use ($quote, $responsable) {
            // Un devis d'avenant est rattaché à une commande existante :
            // ses lignes viennent enrichir la commande et faire évoluer le budget du chantier.
            if ($quote->is_avenant) {
                return $this->acceptAvenant($quote);
            }

            $chantier = $quote->chantier;
            if (! $chantier) {
                $chantier = Chantier::create([
                    'client_id' => $quote->client_id,
                    'reference' => 'CH-'.date('Y').'-'.strtoupper(uniqid()),
                    'name' => 'Chantier issu du devis '.$quote->reference,
                    'status' => ChantierStatus::PLANNED,
                    'budget_total_ht' => $quote->total_ht,
                    // address, zip_code, city à reprendre depuis le client par défaut
                    'address' => $quote->client->addresses()->first()?->street ?? 'À définir',
                    'zip_code' => $quote->client->addresses()->first()?->zip_code ?? '00000',
                    'city' => $quote->client->addresses()->first()?->city ?? 'À définir',
                ]);
                $quote->update(['chantier_id' => $chantier->id]);
            }

            // 3. Création de la Commande Client (Contrat de base pour les situations)
            $order = CustomerOrder::create([
                'client_id' => $quote->client_id,
                'chantier_id' => $chantier->id,
                'customer_quote_id' => $quote->id,
                'responsable_id' => $responsable->id, // Ajout du responsable
                'reference' => app(CustomerOrderService::class)->generateReferenceOrder(),
                'status' => OrderStatus::CONFIRMED,
                'total_ht' => $quote->total_ht,
                'total_ttc' => $quote->total_ttc,
            ]);

            $quote->load('items');

            $itemsToCreate = $quote->items->map(function ($quoteItem) {
                return [
                    'item_id' => $quoteItem->item_id,
                    'purchase_price' => $quoteItem->purchase_price, // Ajout du prix d'achat
                    'name' => $quoteItem->name,
                    'quantity' => $quoteItem->quantity,
                    'selling_price' => $quoteItem->selling_price,
                    'vat_rate_id' => $quoteItem->vat_rate_id,
                    'total_ht' => $quoteItem->selling_price * $quoteItem->quantity,
                ];
            });

            if ($itemsToCreate->isNotEmpty()) {
                $order->items()->createMany($itemsToCreate->all());
            }

            $quote->update([
                'status' => QuoteStatus::SIGNED,
                'signed_at' => now(),
            ]);

            return $order;
        });
    }

    public function generateReferenceQuote(): string
    {
        $year = date('Y');
        $latestQuote = CustomerQuote::where('reference', 'like', "DEV-{$year}-%")
            ->orderByRaw('LENGTH(reference) DESC')
            ->orderBy('reference', 'desc')
            ->first();

        $sequenceNumber = 1;
        if ($latestQuote) {
            // Extract the numeric part after 'DVS-YYYY-'
            $parts = explode('-', $latestQuote->reference);
            if (count($parts) === 3 && is_numeric($parts[2])) {
                $sequenceNumber = (int) $parts[2] + 1;
            }
        }

        return "DEV-{$year}-".str_pad($sequenceNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Génère la référence d'un avenant : AV-YYYY-NNN.
     */
    public function generateReferenceAvenant(): string
    {
        $year = date('Y');
        $latest = CustomerQuote::where('reference', 'like', "AV-{$year}-%")
            ->orderByRaw('LENGTH(reference) DESC')
            ->orderBy('reference', 'desc')
            ->first();

        $sequenceNumber = 1;
        if ($latest) {
            $parts = explode('-', $latest->reference);
            if (count($parts) === 3 && is_numeric($parts[2])) {
                $sequenceNumber = (int) $parts[2] + 1;
            }
        }

        return "AV-{$year}-".str_pad($sequenceNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Accepte un avenant : enrichit la commande principale de ses lignes
     * et rehausse le budget du chantier.
     */
    protected function acceptAvenant(CustomerQuote $quote): CustomerOrder
    {
        $order = CustomerOrder::findOrFail($quote->parent_order_id);
        $chantier = $order->chantier;

        $quote->load('items');

        $itemsToCreate = $quote->items->map(function ($quoteItem) {
            return [
                'item_id' => $quoteItem->item_id,
                'name' => $quoteItem->name,
                'quantity' => $quoteItem->quantity,
                'selling_price' => $quoteItem->selling_price,
                'vat_rate_id' => $quoteItem->vat_rate_id,
                'total_ht' => $quoteItem->selling_price * $quoteItem->quantity,
            ];
        });

        if ($itemsToCreate->isNotEmpty()) {
            $order->items()->createMany($itemsToCreate->all());
        }

        $order->update([
            'total_ht' => (float) $order->total_ht + (float) $quote->total_ht,
            'total_ttc' => (float) $order->total_ttc + (float) $quote->total_ttc,
        ]);

        if ($chantier) {
            $chantier->increment('budget_total_ht', (float) $quote->total_ht);
        }

        $quote->update([
            'status' => QuoteStatus::SIGNED,
            'signed_at' => now(),
            'chantier_id' => $order->chantier_id,
        ]);

        return $order;
    }
}
