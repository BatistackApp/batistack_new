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

    public function __construct(
        protected Item $updatedItem,
        protected array $visited = [],
    ) {}

    public function handle(ItemService $itemService): void
    {
        $this->visited[] = $this->updatedItem->id;

        $parentCompositions = ItemComposition::where('child_item_id', $this->updatedItem->id)->get();

        foreach ($parentCompositions as $composition) {
            $parentItem = $composition->parentItem;

            if (in_array($parentItem->id, $this->visited)) {
                Log::warning("Cycle détecté dans les compositions : ouvrage {$parentItem->reference} (ID {$parentItem->id}) déjà traité.");

                continue;
            }

            try {
                $costs = $itemService->calculateDetailedCost($parentItem);

                $parentItem->updateQuietly([
                    'purchase_price' => $costs['total_cost'],
                ]);

                static::dispatch($parentItem, $this->visited);

            } catch (ArticlesModuleException $exception) {
                Log::error("Échec du recalcul pour l'ouvrage {$parentItem->reference} : ".$exception->getMessage());
                $exception->notify();
            }
        }
    }
}
