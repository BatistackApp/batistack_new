<?php

namespace App\Observers\Gpao;

use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Gpao\ManufacturingOrder;
use App\Services\Gpao\MrpService;
use App\Services\Gpao\ProductionInventoryService;
use App\Services\Articles\StockService;

class ManufacturingOrderObserver
{
    /**
     * Handle the ManufacturingOrder "created" event.
     */
    public function created(ManufacturingOrder $manufacturingOrder): void
    {
        // Générer les besoins initiaux
        (new MrpService())->generateRequirementsForOrder($manufacturingOrder);
    }

    /**
     * Handle the ManufacturingOrder "updated" event.
     */
    public function updated(ManufacturingOrder $manufacturingOrder): void
    {
        // Si la quantité prévue change, on régénère le MRP (uniquement si ce n'est pas encore consommé)
        if ($manufacturingOrder->wasChanged('quantity_planned') && $manufacturingOrder->status === ManufacturingStatus::DRAFT) {
            (new MrpService())->generateRequirementsForOrder($manufacturingOrder);
        }

        // Si le statut passe à EN COURS, on déstocke la matière première
        if ($manufacturingOrder->wasChanged('status') && $manufacturingOrder->status === ManufacturingStatus::IN_PROGRESS) {
            (new ProductionInventoryService(new StockService()))->consumeMaterials($manufacturingOrder);
        }

        // Si le statut passe à COMPLETED, on rentre le produit fini en stock
        // Note : Si l'OF passe à QUALITY_CONTROL, on ne rentre pas encore en stock
        if ($manufacturingOrder->wasChanged('status') && $manufacturingOrder->status === ManufacturingStatus::COMPLETED) {
            (new ProductionInventoryService(new StockService()))->receiveFinishedProduct($manufacturingOrder);
            
            // Générer l'étiquette / PDF de l'OF de façon asynchrone
            \App\Jobs\Gpao\GenerateManufacturingOrderPdfJob::dispatch($manufacturingOrder->id);
        }
    }

    /**
     * Handle the ManufacturingOrder "deleted" event.
     */
    public function deleted(ManufacturingOrder $manufacturingOrder): void
    {
        //
    }
}
