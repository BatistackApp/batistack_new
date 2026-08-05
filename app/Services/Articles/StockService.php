<?php

namespace App\Services\Articles;

use App\Exceptions\Articles\ArticlesModuleException;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use DB;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class StockService
{
    /**
     * Enregistre une entrée en stock et recalcule le PUMP.
     *
     * @throws Throwable
     */
    public function entry(Item $item, Warehouse $warehouse, float $quantity, float $purchasePrice): void
    {
        DB::transaction(function () use ($item, $warehouse, $quantity, $purchasePrice) {
            $currentGlobalStock = $item->stocks()->sum('quantity');
            $oldPump = (float) $item->purchase_price;

            // Formule PUMP : ((Stock_Initial * PUMP_Ancien) + (Qté_Reçue * Prix_Achat)) / Total_Stock
            $totalStock = $currentGlobalStock + $quantity;

            if ($totalStock > 0) {
                $newPump = (($currentGlobalStock * $oldPump) + ($quantity * $purchasePrice)) / $totalStock;
                $item->update(['purchase_price' => round($newPump, 4)]);
            }

            // Mise à jour du stock physique dans le dépôt
            $stock = Stock::firstOrNew([
                'item_id' => $item->id,
                'warehouse_id' => $warehouse->id,
            ]);

            $quantityBefore = $stock->quantity ?? 0.0;
            $stock->quantity += $quantity;
            $stock->save();

            \App\Models\Articles\StockMouvement::create([
                'stock_id' => $stock->id,
                'user_id' => auth()->id(),
                'type' => \App\Enums\Articles\StockMouvementType::IN,
                'reference_type' => \App\Enums\Articles\StockMouvementSource::INTERNAL,
                'reference_id' => null,
                'quantity_before' => $quantityBefore,
                'quantity_delta' => $quantity,
                'quantity_after' => $stock->quantity,
                'reason' => 'Entrée de stock',
            ]);
        });
    }

    public function exit(Item $item, Warehouse $warehouse, float $quantity, string $reason, ?\App\Enums\Articles\StockMouvementSource $source = null, ?int $referenceId = null): void
    {
        $stock = Stock::where('item_id', $item->id)
            ->where('warehouse_id', $warehouse->id)
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

        $quantityBefore = $stock->quantity;
        $stock->quantity -= $quantity;
        $stock->save();

        \App\Models\Articles\StockMouvement::create([
            'stock_id' => $stock->id,
            'user_id' => auth()->id(),
            'type' => \App\Enums\Articles\StockMouvementType::OUT,
            'reference_type' => $source ?? \App\Enums\Articles\StockMouvementSource::INTERNAL,
            'reference_id' => $referenceId,
            'quantity_before' => $quantityBefore,
            'quantity_delta' => -$quantity,
            'quantity_after' => $stock->quantity,
            'reason' => $reason,
        ]);

        // Logique d'alerte si stock < seuil
        if ($stock->quantity <= $stock->min_threshold) {
            Notification::make()
                ->warning()
                ->title('Stock bas dans le dépôt')
                ->body("Stock bas dans le dépôt {$warehouse->name} pour l'article {$item->reference}.")
                ->send();
        }
    }

    /**
     * Consomme du stock préalablement réservé (diminue le stock physique ET le stock réservé).
     * @throws Throwable
     */
    public function consumeReserved(Item $item, Warehouse $warehouse, float $quantity, string $reason, ?\App\Enums\Articles\StockMouvementSource $source = null, ?int $referenceId = null): void
    {
        $stock = Stock::where('item_id', $item->id)
            ->where('warehouse_id', $warehouse->id)
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

        DB::transaction(function () use ($stock, $quantity, $reason, $source, $referenceId) {
            $stock->reserved_quantity -= $quantity;
            $quantityBefore = $stock->quantity;
            $stock->quantity -= $quantity;
            $stock->save();

            \App\Models\Articles\StockMouvement::create([
                'stock_id' => $stock->id,
                'user_id' => auth()->id(),
                'type' => \App\Enums\Articles\StockMouvementType::OUT,
                'reference_type' => $source ?? \App\Enums\Articles\StockMouvementSource::INTERNAL,
                'reference_id' => $referenceId,
                'quantity_before' => $quantityBefore,
                'quantity_delta' => -$quantity,
                'quantity_after' => $stock->quantity,
                'reason' => $reason . ' (Stock Réservé)',
            ]);
        });

        // Logique d'alerte si stock < seuil
        if ($stock->quantity <= $stock->min_threshold) {
            Notification::make()
                ->warning()
                ->title('Stock bas dans le dépôt')
                ->body("Stock bas dans le dépôt {$warehouse->name} pour l'article {$item->reference}.")
                ->send();
        }
    }

    /**
     * Réserve du stock
     */
    public function reserve(Item $item, Warehouse $warehouse, float $quantity): void
    {
        $stock = Stock::where('item_id', $item->id)->where('warehouse_id', $warehouse->id)->first();
        if (! $stock) {
            throw new ArticlesModuleException("Stock introuvable.", 404);
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
            throw new ArticlesModuleException("Stock introuvable.", 404);
        }
        $stock->release($quantity);
    }

    /**
     * Transfère du stock entre deux dépôts.
     * @throws Throwable
     */
    public function transfer(Item $item, Warehouse $from, Warehouse $to, float $quantity): void
    {
        DB::transaction(function () use ($item, $from, $to, $quantity) {
            $this->exit($item, $from, $quantity, "Transfert vers {$to->name}");
            $this->entry($item, $to, $quantity, $item->purchase_price);
        });
    }

    /**
     * Transfère un Kit complet (et ses composants) entre deux dépôts.
     * Si l'un des composants est en rupture, la transaction est annulée.
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
}
