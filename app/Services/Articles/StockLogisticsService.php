<?php

namespace App\Services\Articles;

use App\Enums\Articles\StockMouvementSource;
use App\Enums\Articles\StockMouvementType;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\StockMouvement;
use App\Models\Articles\Warehouse;
use App\Models\Chantiers\Chantier;
use Exception;
use Illuminate\Support\Facades\DB;

class StockLogisticsService
{
    /**
     * Get or create the virtual warehouse for a chantier.
     */
    public function getOrCreateVirtualWarehouse(Chantier $chantier): Warehouse
    {
        return Warehouse::firstOrCreate(
            ['chantier_id' => $chantier->id],
            [
                'name' => 'Chantier: '.$chantier->name,
                'location' => $chantier->address.', '.$chantier->zip_code.' '.$chantier->city,
                'is_active' => true,
            ]
        );
    }

    /**
     * Transfer stock from a depot to a virtual chantier warehouse.
     *
     * @throws Exception
     */
    public function transferToChantier(Warehouse $source, Chantier $chantier, Item $item, float $quantity, int $userId, ?string $sourceLocationCode = null): void
    {
        if ($quantity <= 0) {
            throw new Exception('La quantité à transférer doit être supérieure à 0.');
        }

        DB::transaction(function () use ($source, $chantier, $item, $quantity, $userId, $sourceLocationCode) {
            $sourceStock = $source->stocks()
                ->where('item_id', $item->id)
                ->lockForUpdate()
                ->first();

            if (! $sourceStock || $sourceStock->quantity < $quantity) {
                throw new Exception("Quantité insuffisante dans l'entrepôt source.");
            }

            $destination = $this->getOrCreateVirtualWarehouse($chantier);

            // Mouvement de sortie (Dépôt)
            $sourceStock->decrement('quantity', $quantity);
            StockMouvement::create([
                'stock_id' => $sourceStock->id,
                'user_id' => $userId,
                'type' => StockMouvementType::OUT,
                'quantity_before' => $sourceStock->quantity + $quantity,
                'quantity_delta' => -$quantity,
                'quantity_after' => $sourceStock->quantity,
                'description' => "Transfert vers chantier {$chantier->name}",
                'reference_type' => StockMouvementSource::SITE,
                'reference_id' => $chantier->id,
            ]);

            // Déduction FIFO ou bin cible source
            app(StockService::class)->deductFromLocations($sourceStock, $quantity, $sourceLocationCode);

            // Mouvement d'entrée (Chantier)
            $destStock = $destination->stocks()->firstOrCreate(
                ['item_id' => $item->id],
                ['quantity' => 0, 'min_threshold' => 0]
            );
            $destStock->increment('quantity', $quantity);
            StockMouvement::create([
                'stock_id' => $destStock->id,
                'user_id' => $userId,
                'type' => StockMouvementType::IN,
                'quantity_before' => $destStock->quantity - $quantity,
                'quantity_delta' => $quantity,
                'quantity_after' => $destStock->quantity,
                'description' => "Réception depuis l'entrepôt {$source->name}",
                'reference_type' => StockMouvementSource::INTERNAL,
                'reference_id' => $source->id,
            ]);

            // Créer un emplacement par défaut au chantier
            app(StockService::class)->upsertLocation($destStock, 'CHANTIER', $quantity);
        });
    }

    /**
     * Consume stock on a chantier site (FIFO).
     *
     * @throws Exception
     */
    public function consumeOnSite(Chantier $chantier, Item $item, float $quantity, int $userId, ?string $description = null): void
    {
        if ($quantity <= 0) {
            throw new Exception('La quantité à consommer doit être supérieure à 0.');
        }

        DB::transaction(function () use ($chantier, $item, $quantity, $userId, $description) {
            $warehouse = Warehouse::where('chantier_id', $chantier->id)->first();
            if (! $warehouse) {
                throw new Exception("Aucun stock n'a été transféré vers ce chantier.");
            }

            $stock = $warehouse->stocks()->where('item_id', $item->id)->lockForUpdate()->first();
            if (! $stock || $stock->quantity < $quantity) {
                throw new Exception('Quantité insuffisante sur le chantier.');
            }

            $stock->decrement('quantity', $quantity);
            StockMouvement::create([
                'stock_id' => $stock->id,
                'user_id' => $userId,
                'type' => StockMouvementType::OUT,
                'quantity_before' => $stock->quantity + $quantity,
                'quantity_delta' => -$quantity,
                'quantity_after' => $stock->quantity,
                'description' => $description ?: 'Consommation sur chantier',
                'reference_type' => StockMouvementSource::SITE,
                'reference_id' => $chantier->id,
            ]);

            // FIFO sur les emplacements du chantier
            app(StockService::class)->deductFromLocations($stock, $quantity);
        });
    }

    /**
     * Return remaining stock from a chantier to a depot (FIFO).
     *
     * @throws Exception
     */
    public function returnToDepot(Chantier $chantier, Warehouse $destination, Item $item, float $quantity, int $userId): void
    {
        if ($quantity <= 0) {
            throw new Exception('La quantité à retourner doit être supérieure à 0.');
        }

        DB::transaction(function () use ($chantier, $destination, $item, $quantity, $userId) {
            $source = Warehouse::where('chantier_id', $chantier->id)->first();
            if (! $source) {
                throw new Exception('Le chantier ne possède aucun entrepôt virtuel.');
            }

            $sourceStock = $source->stocks()->where('item_id', $item->id)->lockForUpdate()->first();
            if (! $sourceStock || $sourceStock->quantity < $quantity) {
                throw new Exception('Quantité insuffisante sur le chantier pour ce retour.');
            }

            // Mouvement de sortie (Chantier)
            $sourceStock->decrement('quantity', $quantity);
            StockMouvement::create([
                'stock_id' => $sourceStock->id,
                'user_id' => $userId,
                'type' => StockMouvementType::OUT,
                'quantity_before' => $sourceStock->quantity + $quantity,
                'quantity_delta' => -$quantity,
                'quantity_after' => $sourceStock->quantity,
                'description' => "Retour depuis le chantier {$chantier->name}",
                'reference_type' => StockMouvementSource::RETURN,
                'reference_id' => $chantier->id,
            ]);

            // FIFO sur les emplacements du chantier
            app(StockService::class)->deductFromLocations($sourceStock, $quantity);

            // Mouvement d'entrée (Dépôt)
            $destStock = $destination->stocks()->firstOrCreate(
                ['item_id' => $item->id],
                ['quantity' => 0, 'min_threshold' => 0]
            );
            $destStock->increment('quantity', $quantity);
            StockMouvement::create([
                'stock_id' => $destStock->id,
                'user_id' => $userId,
                'type' => StockMouvementType::IN,
                'quantity_before' => $destStock->quantity - $quantity,
                'quantity_delta' => $quantity,
                'quantity_after' => $destStock->quantity,
                'description' => "Retour matériel du chantier {$chantier->name}",
                'reference_type' => StockMouvementSource::RETURN,
                'reference_id' => $chantier->id,
            ]);

            // Créer un emplacement par défaut au dépôt
            app(StockService::class)->upsertLocation($destStock, 'RETOUR', $quantity);
        });
    }
}
