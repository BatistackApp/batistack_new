<?php

namespace App\Observers\Gpao;

use App\Enums\Gpao\ManufacturingStatus;
use App\Jobs\Gpao\GenerateManufacturingOrderPdfJob;
use App\Models\Gpao\MachineMaintenanceTicket;
use App\Models\Gpao\ManufacturingOrder;
use App\Services\Articles\StockService;
use App\Services\Gpao\MrpService;
use App\Services\Gpao\ProductionInventoryService;

class ManufacturingOrderObserver
{
    /**
     * Handle the ManufacturingOrder "created" event.
     */
    public function created(ManufacturingOrder $manufacturingOrder): void
    {
        // Générer les besoins initiaux
        (new MrpService)->generateRequirementsForOrder($manufacturingOrder);
    }

    /**
     * Handle the ManufacturingOrder "updated" event.
     */
    public function updated(ManufacturingOrder $manufacturingOrder): void
    {
        // Si la quantité prévue change, on régénère le MRP (uniquement si ce n'est pas encore consommé)
        if ($manufacturingOrder->wasChanged('quantity_planned') && $manufacturingOrder->status === ManufacturingStatus::DRAFT) {
            (new MrpService)->generateRequirementsForOrder($manufacturingOrder);
        }

        // Le déstockage (Backflush) se fera à la complétion de l'OF
        // pour s'aligner sur la validation finale.

        // Si le statut passe à COMPLETED, on rentre le produit fini en stock
        // Note : Si l'OF passe à QUALITY_CONTROL, on ne rentre pas encore en stock
        if ($manufacturingOrder->wasChanged('status') && $manufacturingOrder->status === ManufacturingStatus::COMPLETED) {
            // 1. Décrémenter les matériaux (Backflush)
            (new ProductionInventoryService(new StockService))->consumeMaterials($manufacturingOrder);

            // 2. Entrer le produit fini
            (new ProductionInventoryService(new StockService))->receiveFinishedProduct($manufacturingOrder);

            // Calculer le coût réel de la main d'œuvre en fonction des pointages (TimeEntries)
            $totalLaborCost = 0;
            foreach ($manufacturingOrder->timeEntries()->with('employee.currentContract')->get() as $entry) {
                if ($entry->hours > 0 && $entry->employee && $entry->employee->currentContract) {
                    $hourlyRate = $entry->employee->currentContract->hourly_rate ?? 0;
                    $totalLaborCost += $entry->hours * $hourlyRate;
                }
            }
            // Mettre à jour sans déclencher d'événements
            $manufacturingOrder->updateQuietly(['total_labor_cost' => $totalLaborCost]);

            // Mettre à jour l'utilisation des machines
            $this->updateMachineUsage($manufacturingOrder);

            // Générer l'étiquette / PDF de l'OF de façon asynchrone
            GenerateManufacturingOrderPdfJob::dispatch($manufacturingOrder->id);
        }
    }

    protected function updateMachineUsage(ManufacturingOrder $order): void
    {
        $hours = $order->timeEntries()->sum('hours') ?: 4; // Mock: 4 hours si non tracé

        foreach ($order->machines as $machine) {
            $machine->increment('usage_hours', $hours);

            if ($machine->usage_hours >= $machine->maintenance_interval_hours) {
                $hasOpenTicket = MachineMaintenanceTicket::where('machine_id', $machine->id)
                    ->where('type', 'preventive')
                    ->where('status', 'open')
                    ->exists();

                if (! $hasOpenTicket) {
                    MachineMaintenanceTicket::create([
                        'machine_id' => $machine->id,
                        'type' => 'preventive',
                        'status' => 'open',
                        'description' => 'Maintenance préventive requise suite au dépassement du seuil d\'utilisation.',
                    ]);
                }
            }
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
