<?php

namespace App\Services\Gpao;

use App\Models\Gpao\ManufacturingOrder;
use App\Models\Gpao\Machine;
use App\Enums\Gpao\ManufacturingStatus;

class ApsSchedulingService
{
    /**
     * Optimizes scheduling of OPEN manufacturing orders.
     * Takes into account customer order delivery date (mix logic) and material availability.
     */
    public function scheduleOpenOrders(): void
    {
        $orders = ManufacturingOrder::where('status', ManufacturingStatus::OPEN)
            ->with(['customerOrder', 'requirements.item'])
            ->get();

        // Sort by priority (mix of urgency and material availability)
        $sortedOrders = $orders->sortBy(function ($order) {
            // 1. If material is not available, it gets lower priority (high number)
            $materialAvailable = $this->isMaterialAvailable($order);
            
            // 2. Deadline score (closer deadline = lower score)
            $deadline = $order->customerOrder?->delivery_date;
            $deadlineScore = $deadline ? $deadline->timestamp : now()->addYear()->timestamp;

            // Material available = 0, not available = 1 (pushes it down the list)
            $availabilityScore = $materialAvailable ? 0 : 10000000000;

            return $availabilityScore + $deadlineScore;
        });

        // Simple scheduling assigning them back-to-back on available operational machines
        $machines = Machine::where('status', \App\Enums\Gpao\MachineStatus::OPERATIONAL)->get();
        if ($machines->isEmpty()) {
            return;
        }

        $machineAvailability = [];
        foreach ($machines as $machine) {
            $machineAvailability[$machine->id] = now();
        }

        foreach ($sortedOrders as $order) {
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
