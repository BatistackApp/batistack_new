<?php

namespace App\Services\Gpao;

use App\Enums\Gpao\MachineStatus;
use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Gpao\Machine;
use App\Models\Gpao\ManufacturingOrder;

class ApsSchedulingService
{
    /**
     * Optimizes scheduling of PLANNED manufacturing orders.
     * Takes into account customer order delivery date (mix logic) and material availability.
     * Returns an array of orders that could not be scheduled due to material shortages.
     */
    public function scheduleOpenOrders(): array
    {
        $orders = ManufacturingOrder::where('status', ManufacturingStatus::PLANNED)
            ->with(['customerOrder', 'requirements.item'])
            ->get();

        // Sort by priority (mix of urgency and material availability)
        $sortedOrders = $orders->sortBy(function ($order) {
            // Deadline score (closer deadline = lower score)
            $deadline = $order->customerOrder?->delivery_date;

            return $deadline ? $deadline->timestamp : now()->addYear()->timestamp;
        });

        // Simple scheduling assigning them back-to-back on available operational machines
        $machines = Machine::where('status', MachineStatus::OPERATIONAL)->get();
        if ($machines->isEmpty()) {
            return [];
        }

        $machineAvailability = [];
        foreach ($machines as $machine) {
            $machineAvailability[$machine->id] = now();
        }

        $shortages = [];

        foreach ($sortedOrders as $order) {
            if (! $this->isMaterialAvailable($order)) {
                $shortages[] = $order;

                continue;
            }

            // Find machine with earliest availability
            asort($machineAvailability);
            $bestMachineId = array_key_first($machineAvailability);

            $startTime = $machineAvailability[$bestMachineId];

            // Assign to order
            $order->update([
                'start_date' => $startTime->toDateString(),
            ]);
            $order->machines()->sync([$bestMachineId]);

            // Estimate duration (simple mock: 4 hours per order)
            $durationHours = 4;

            // Update machine availability for the next order
            $machineAvailability[$bestMachineId] = $startTime->addHours($durationHours);
        }

        return $shortages;
    }

    protected function isMaterialAvailable(ManufacturingOrder $order): bool
    {
        foreach ($order->requirements as $req) {
            if ($req->item && $req->item->stock < $req->quantity_required) {
                return false;
            }
        }

        return true;
    }
}
