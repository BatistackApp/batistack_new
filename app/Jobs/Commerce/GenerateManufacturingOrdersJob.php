<?php

namespace App\Jobs\Commerce;

use App\Enums\Articles\ItemType;
use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Articles\Item;
use App\Models\Commerce\CustomerOrder;
use App\Models\Gpao\ManufacturingOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class GenerateManufacturingOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public CustomerOrder $order
    ) {}

    public function handle(): void
    {
        // On récupère la commande avec ses lignes et articles
        $this->order->load('items.item.components.childItem');

        foreach ($this->order->items as $orderItem) {
            $item = $orderItem->item;
            
            // Si c'est un Ouvrage (WORK), on génère un OF
            if ($item && $item->type === ItemType::WORK) {
                $this->generateOFForItem($item, $orderItem->quantity, null);
            }
        }
    }

    /**
     * Génère récursivement un OF pour un article et ses sous-composants
     */
    protected function generateOFForItem(Item $item, float $quantity, ?int $parentId): void
    {
        // 1. Créer l'OF pour cet article
        $of = ManufacturingOrder::create([
            'reference' => 'OF-' . strtoupper(uniqid()),
            'item_id' => $item->id,
            'chantier_id' => $this->order->chantier_id,
            'customer_order_id' => $this->order->id,
            'parent_id' => $parentId,
            'quantity_planned' => $quantity,
            'status' => ManufacturingStatus::PLANNED,
        ]);

        // 2. Parcourir la nomenclature de l'article pour trouver les sous-ouvrages
        if ($item->relationLoaded('components') || $item->components()->exists()) {
            $components = $item->components()->with('childItem')->get();
            
            foreach ($components as $component) {
                $childItem = $component->childItem;
                if ($childItem && $childItem->type === ItemType::WORK) {
                    // C'est un sous-ouvrage, on génère un sous-OF avec la quantité requise
                    $requiredQuantity = $quantity * $component->quantity;
                    $this->generateOFForItem($childItem, $requiredQuantity, $of->id);
                }
            }
        }
    }
}
