<?php

namespace App\Services\Commerce;

use App\Enums\Chantiers\ChantierStatus;
use App\Enums\Commerce\OrderStatus;
use App\Enums\Commerce\QuoteStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerOrder;
use App\Models\Commerce\CustomerQuote;
use DB;
use Exception;

class QuoteService
{
    /**
     * Valide un devis client, génère la commande officielle et initialise le chantier.
     * @throws Exception
     * @throws \Throwable
     */
    public function acceptQuote(CustomerQuote $quote): CustomerOrder
    {
        if ($quote->status !== QuoteStatus::SENT && $quote->status !== QuoteStatus::DRAFT) {
            throw new Exception('Ce devis ne peut pas être accepté dans son état actuel.');
        }

        return DB::transaction(function () use ($quote) {
            // 1. Mise à jour du statut du devis
            $quote->update([
                'status' => QuoteStatus::SIGNED,
                'signed_at' => now(),
            ]);

            // 2. Création ou mise à jour du Chantier lié
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
                'reference' => 'CMD-'.str_replace('DEV-', '', $quote->reference),
                'status' => OrderStatus::CONFIRMED,
                'total_ht' => $quote->total_ht,
                'total_ttc' => $quote->total_ttc,
            ]);

            // 4. Duplication immuable des lignes (Snapshot)
            foreach ($quote->items as $item) {
                $order->items()->create([
                    'item_id' => $item->item_id,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'selling_price' => $item->selling_price,
                    'vat_rate_id' => $item->vat_rate_id,
                ]);
            }

            return $order;
        });
    }
}
