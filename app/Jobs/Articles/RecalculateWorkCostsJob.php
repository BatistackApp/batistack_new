<?php

namespace App\Jobs\Articles;

use App\Exceptions\Articles\ArticlesModuleException;
use App\Models\Articles\Item;
use App\Models\Articles\ItemComposition;
use App\Services\Articles\ItemService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecalculateWorkCostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Item $updatedItem) {}

    public function handle(ItemService $itemService): void
    {
        // On cherche toutes les compositions où cet article est un enfant
        $parentCompositions = ItemComposition::where('child_item_id', $this->updatedItem->id)->get();

        foreach ($parentCompositions as $composition) {
            $parentItem = $composition->parentItem;

            try {
                // Calcul du nouveau coût détaillé
                $costs = $itemService->calculateDetailedCost($parentItem);

                // Mise à jour du prix d'achat (coût de revient) de l'ouvrage
                $parentItem->updateQuietly([
                    'purchase_price' => $costs['total_cost'],
                ]);

                // Récursion : Si cet ouvrage est lui-même un composant d'un autre ouvrage
                static::dispatch($parentItem);

            } catch (ArticlesModuleException $exception) {
                Log::error("Échec du recalcul pour l'ouvrage {$parentItem->reference} : ".$exception->getMessage());
                $exception->notify();
            }
        }
    }
}
