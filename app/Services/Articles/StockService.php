<?php

namespace App\Services\Articles;

use App\Enums\Articles\StockMouvementSource;
use App\Enums\Articles\StockMouvementType;
use App\Exceptions\Articles\ArticlesModuleException;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\StockLocation;
use App\Models\Articles\StockMouvement;
use App\Models\Articles\Warehouse;
use DB;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class StockService
{
    /**
     * Crée un mouvement de stock (entrée ou sortie).
     *
     * @throws Throwable
     */
    public function createMouvement(Item $item, Warehouse $warehouse, string $type, float $quantity, ?string $description, ?StockMouvementSource $source = null, ?int $referenceId = null, ?string $batchNumber = null, ?string $expirationDate = null, ?string $locationCode = null): void
    {
        if ($quantity <= 0) {
            throw new ArticlesModuleException('La quantité doit être strictement positive.', 400);
        }

        if ($type === 'in') {
            $this->entry($item, $warehouse, $quantity, $item->purchase_price, $batchNumber, $expirationDate, $locationCode);
        } else {
            $this->exit($item, $warehouse, $quantity, $description, $source, $referenceId, $batchNumber, $expirationDate, $locationCode);
        }
    }

    /**
     * Enregistre une entrée en stock et recalcule le PUMP.
     *
     * @throws Throwable
     */
    public function entry(Item $item, Warehouse $warehouse, float $quantity, float $purchasePrice, ?string $batchNumber = null, ?string $expirationDate = null, ?string $locationCode = null, ?StockMouvementSource $source = null): void
    {
        if ($item->is_sensitive && (empty($batchNumber) || empty($expirationDate))) {
            throw new ArticlesModuleException(
                'Un numéro de lot et une date de péremption sont requis pour un article sensible.',
                400
            );
        }

        DB::transaction(function () use ($item, $warehouse, $quantity, $purchasePrice, $batchNumber, $expirationDate, $locationCode, $source) {
            $currentGlobalStock = $item->stocks()->sum('quantity');
            $oldPump = (float) $item->purchase_price;

            // Formule PUMP : ((Stock_Initial * PUMP_Ancien) + (Qté_Reçue * Prix_Achat)) / Total_Stock
            $totalStock = $currentGlobalStock + $quantity;

            if ($totalStock > 0) {
                $newPump = (($currentGlobalStock * $oldPump) + ($quantity * $purchasePrice)) / $totalStock;
                $item->update(['purchase_price' => round($newPump, 4)]);
            }

            // Mise à jour du stock physique dans le dépôt
            $stock = Stock::where('item_id', $item->id)
                ->where('warehouse_id', $warehouse->id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = Stock::create([
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity' => 0,
                ]);
            }

            $quantityBefore = $stock->quantity ?? 0.0;
            $stock->quantity += $quantity;
            $stock->save();

            StockMouvement::create([
                'stock_id' => $stock->id,
                'user_id' => auth()->id(),
                'type' => StockMouvementType::IN,
                'reference_type' => $source ?? StockMouvementSource::INTERNAL,
                'reference_id' => null,
                'quantity_before' => $quantityBefore,
                'quantity_delta' => $quantity,
                'quantity_after' => $stock->quantity,
                'reason' => 'Entrée de stock',
                'batch_number' => $batchNumber,
                'expiration_date' => $expirationDate,
            ]);

            // Emplacement physique
            if ($locationCode !== null) {
                $this->upsertLocation($stock, $locationCode, $quantity);
            }
        });
    }

    public function exit(Item $item, Warehouse $warehouse, float $quantity, ?string $reason = null, ?StockMouvementSource $source = null, ?int $referenceId = null, ?string $batchNumber = null, ?string $expirationDate = null, ?string $locationCode = null): void
    {
        DB::transaction(function () use ($item, $warehouse, $quantity, $reason, $source, $referenceId, $batchNumber, $expirationDate, $locationCode) {
            $stock = Stock::where('item_id', $item->id)
                ->where('warehouse_id', $warehouse->id)
                ->lockForUpdate()
                ->first();

            if (! $stock || $stock->getAvailableQuantity() < $quantity) {
                // Note : Selon la config (E3), on peut autoriser le stock négatif ou bloquer.
                // Ici, nous lançons une exception par sécurité.
                Log::alert("Stock insuffisant dans le dépôt {$warehouse->name} pour l'article {$item->reference}.");
                $exception = new ArticlesModuleException(
                    message: "Stock insuffisant dans le dépôt {$warehouse->name} pour l'article {$item->reference}.",
                    code: 400,
                );
                $exception->notify();
                throw $exception;
            }

            if ($item->is_sensitive && empty($batchNumber)) {
                throw new ArticlesModuleException(
                    'Un numéro de lot est requis pour une sortie d\'un article sensible.',
                    400
                );
            }

            if ($item->is_sensitive && StockMouvement::getRemainingBatchQuantity($stock->id, $batchNumber) < $quantity) {
                throw new ArticlesModuleException(
                    "Quantité insuffisante pour le lot {$batchNumber}.",
                    400
                );
            }

            $quantityBefore = $stock->quantity;
            $stock->quantity -= $quantity;
            $stock->save();

            StockMouvement::create([
                'stock_id' => $stock->id,
                'user_id' => auth()->id(),
                'type' => StockMouvementType::OUT,
                'reference_type' => $source ?? StockMouvementSource::INTERNAL,
                'reference_id' => $referenceId,
                'quantity_before' => $quantityBefore,
                'quantity_delta' => -$quantity,
                'quantity_after' => $stock->quantity,
                'reason' => $reason ?? 'Sortie de stock',
                'batch_number' => $batchNumber,
                'expiration_date' => $expirationDate,
            ]);

            // Déduction de l'emplacement physique
            $this->deductFromLocations($stock, $quantity, $locationCode);

            // Logique d'alerte si stock < seuil
            if ($stock->quantity <= $stock->min_threshold) {
                Notification::make()
                    ->warning()
                    ->title('Stock bas dans le dépôt')
                    ->body("Stock bas dans le dépôt {$warehouse->name} pour l'article {$item->reference}.")
                    ->send();
            }
        });
    }

    /**
     * Consomme du stock préalablement réservé (diminue le stock physique ET le stock réservé).
     *
     * @throws Throwable
     */
    public function consumeReserved(Item $item, Warehouse $warehouse, float $quantity, string $reason, ?StockMouvementSource $source = null, ?int $referenceId = null): void
    {
        DB::transaction(function () use ($item, $warehouse, $quantity, $reason, $source, $referenceId) {
            $stock = Stock::where('item_id', $item->id)
                ->where('warehouse_id', $warehouse->id)
                ->lockForUpdate()
                ->first();

            if (! $stock || $stock->reserved_quantity < $quantity) {
                Log::alert("Stock réservé insuffisant dans le dépôt {$warehouse->name} pour l'article {$item->reference}.");
                $exception = new ArticlesModuleException(
                    message: "Stock réservé insuffisant dans le dépôt {$warehouse->name} pour l'article {$item->reference}.",
                    code: 400,
                );
                $exception->notify();
                throw $exception;
            }

            $stock->reserved_quantity -= $quantity;
            $quantityBefore = $stock->quantity;
            $stock->quantity -= $quantity;
            $stock->save();

            StockMouvement::create([
                'stock_id' => $stock->id,
                'user_id' => auth()->id(),
                'type' => StockMouvementType::OUT,
                'reference_type' => $source ?? StockMouvementSource::INTERNAL,
                'reference_id' => $referenceId,
                'quantity_before' => $quantityBefore,
                'quantity_delta' => -$quantity,
                'quantity_after' => $stock->quantity,
                'reason' => $reason.' (Stock Réservé)',
            ]);

            // Logique d'alerte si stock < seuil
            if ($stock->quantity <= $stock->min_threshold) {
                Notification::make()
                    ->warning()
                    ->title('Stock bas dans le dépôt')
                    ->body("Stock bas dans le dépôt {$warehouse->name} pour l'article {$item->reference}.")
                    ->send();
            }
        });
    }

    /**
     * Réserve du stock
     */
    public function reserve(Item $item, Warehouse $warehouse, float $quantity): void
    {
        $stock = Stock::where('item_id', $item->id)->where('warehouse_id', $warehouse->id)->first();
        if (! $stock) {
            throw new ArticlesModuleException('Stock introuvable.', 404);
        }
        $stock->reserve($quantity);
    }

    /**
     * Libère du stock réservé
     */
    public function release(Item $item, Warehouse $warehouse, float $quantity): void
    {
        $stock = Stock::where('item_id', $item->id)->where('warehouse_id', $warehouse->id)->first();
        if (! $stock) {
            throw new ArticlesModuleException('Stock introuvable.', 404);
        }
        $stock->release($quantity);
    }

    /**
     * Transfère du stock entre deux dépôts.
     *
     * @throws Throwable
     */
    public function transfer(Item $item, Warehouse $from, Warehouse $to, float $quantity, ?string $targetLocationCode = null): void
    {
        DB::transaction(function () use ($item, $from, $to, $quantity, $targetLocationCode) {
            $this->exit($item, $from, $quantity, "Transfert vers {$to->name}");
            $this->entry($item, $to, $quantity, $item->purchase_price, null, null, $targetLocationCode);
        });
    }

    /**
     * Transfère un Kit complet (et ses composants) entre deux dépôts.
     * Si l'un des composants est en rupture, la transaction est annulée.
     *
     * @throws Throwable
     */
    public function transferKit(Item $kit, Warehouse $from, Warehouse $to, float $kitQuantity): void
    {
        if (! $kit->isComposed()) {
            throw new ArticlesModuleException(
                message: "L'article {$kit->reference} n'est pas un kit (composition).",
                code: 400
            );
        }

        DB::transaction(function () use ($kit, $from, $to, $kitQuantity) {
            foreach ($kit->components as $composition) {
                // La quantité requise pour un composant est sa quantité unitaire multipliée par le nombre de kits
                $requiredQuantity = $composition->quantity * $kitQuantity;

                // On délègue le transfert du composant.
                // En cas de stock insuffisant, $this->exit() lèvera une exception et annulera la transaction.
                $this->transfer($composition->childItem, $from, $to, $requiredQuantity);
            }
        });
    }

    // ============================================
    // EMPLACEMENTS PHYSIQUES (BIN-PICKING)
    // ============================================

    /**
     * Créer ou mettre à jour un emplacement physique pour un stock.
     */
    public function upsertLocation(Stock $stock, string $locationCode, float $quantity): StockLocation
    {
        $location = StockLocation::firstOrCreate(
            ['stock_id' => $stock->id, 'location_code' => $locationCode],
            ['quantity' => 0]
        );

        $location->quantity += $quantity;
        $location->save();

        return $location;
    }

    /**
     * Déduire la quantité des emplacements physiques (FIFO ou bin cible).
     *
     * @throws ArticlesModuleException
     */
    public function deductFromLocations(Stock $stock, float $quantity, ?string $targetLocationCode = null): void
    {
        $locations = $stock->locations()->hasQuantity()->orderBy('created_at')->orderBy('id')->get();

        if ($locations->isEmpty()) {
            if ($targetLocationCode !== null) {
                throw new ArticlesModuleException(
                    "Aucun emplacement trouvé pour {$targetLocationCode}.",
                    400
                );
            }

            return;
        }

        $remaining = $quantity;

        if ($targetLocationCode !== null) {
            // Cibler un bin spécifique
            $target = $locations->firstWhere('location_code', $targetLocationCode);

            if (! $target || $target->quantity < $remaining) {
                throw new ArticlesModuleException(
                    "Quantité insuffisante dans l'emplacement {$targetLocationCode}.",
                    400
                );
            }

            $target->quantity -= $remaining;
            $target->save();

            return;
        }

        // FIFO : déduire des bins les plus anciens en premier
        foreach ($locations as $location) {
            if ($remaining <= 0) {
                break;
            }

            $toDeduct = min($remaining, $location->quantity);
            $location->quantity -= $toDeduct;
            $location->save();
            $remaining -= $toDeduct;
        }

        if ($remaining > 0.0001) {
            if ($stock->getUnallocatedQuantity() >= $remaining) {
                return;
            }

            throw new ArticlesModuleException(
                'Quantité insuffisante répartie dans les emplacements.',
                400
            );
        }
    }

    /**
     * Déplacer du stock d'un emplacement à un autre dans le même dépôt.
     *
     * @throws ArticlesModuleException
     */
    public function moveLocation(Stock $stock, string $fromCode, string $toCode, float $quantity): void
    {
        if ($quantity <= 0) {
            throw new ArticlesModuleException(
                'La quantité à déplacer doit être strictement positive.',
                400
            );
        }

        DB::transaction(function () use ($stock, $fromCode, $toCode, $quantity) {
            $from = $stock->locations()->where('location_code', $fromCode)->lockForUpdate()->first();

            if (! $from || $from->quantity < $quantity) {
                throw new ArticlesModuleException(
                    "Quantité insuffisante dans l'emplacement source {$fromCode}.",
                    400
                );
            }

            $from->quantity -= $quantity;
            $from->save();

            $this->upsertLocation($stock, $toCode, $quantity);
        });
    }

    /**
     * Assigner du stock à un emplacement (sans mouvement, réorganisation physique).
     *
     * @throws ArticlesModuleException
     */
    public function assignToLocation(Stock $stock, string $locationCode, float $quantity): void
    {
        if ($quantity <= 0) {
            throw new ArticlesModuleException(
                'La quantité assignée doit être positive.',
                400
            );
        }

        DB::transaction(function () use ($stock, $locationCode, $quantity) {
            $stock = Stock::where('id', $stock->id)->lockForUpdate()->first();

            if ($quantity > $stock->getUnallocatedQuantity()) {
                throw new ArticlesModuleException(
                    'La quantité assignée ne peut pas dépasser la quantité non affectée.',
                    400
                );
            }

            $this->upsertLocation($stock, $locationCode, $quantity);
        });
    }
}
