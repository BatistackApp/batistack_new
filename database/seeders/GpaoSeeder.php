<?php

namespace Database\Seeders;

use App\Enums\Gpao\MachineStatus;
use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Articles\Item;
use App\Models\Chantiers\Chantier;
use App\Models\Gpao\Machine;
use App\Models\Gpao\ManufacturingOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GpaoSeeder extends Seeder
{
    public function run(): void
    {
        $items = Item::all();
        $chantiers = Chantier::all();

        if ($items->isEmpty()) {
            return;
        }

        // Machines
        $machines = [
            Machine::create(['name' => 'SCIE CIRCULAIRE SCM', 'reference' => 'MC-001', 'status' => MachineStatus::OPERATIONAL, 'usage_hours' => 1250.00, 'maintenance_interval_hours' => 500.00]),
            Machine::create(['name' => 'DECOLLEUSE AUTOMATIQUE', 'reference' => 'MC-002', 'status' => MachineStatus::OPERATIONAL, 'usage_hours' => 890.00, 'maintenance_interval_hours' => 300.00]),
            Machine::create(['name' => 'FRAISEUSE CNC', 'reference' => 'MC-003', 'status' => MachineStatus::MAINTENANCE, 'usage_hours' => 2100.00, 'maintenance_interval_hours' => 400.00]),
            Machine::create(['name' => 'PRESSE HYDRAULIQUE', 'reference' => 'MC-004', 'status' => MachineStatus::OPERATIONAL, 'usage_hours' => 560.00, 'maintenance_interval_hours' => 600.00]),
        ];

        // Ordres de fabrication
        $statuses = ManufacturingStatus::cases();
        for ($i = 0; $i < 6; $i++) {
            $item = $items->random();
            $startDate = now()->subDays(rand(0, 30));

            ManufacturingOrder::create([
                'uuid' => (string) Str::uuid(),
                'reference' => 'OF-'.now()->year.'-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'item_id' => $item->id,
                'chantier_id' => $chantiers->isNotEmpty() ? $chantiers->random()->id : null,
                'customer_order_id' => null,
                'quantity_planned' => rand(10, 100),
                'quantity_produced' => rand(0, 100),
                'status' => $statuses[array_rand($statuses)],
                'start_date' => $startDate,
                'end_date' => rand(0, 100) > 50 ? (clone $startDate)->addDays(rand(1, 14)) : null,
                'total_labor_cost' => rand(500, 5000) / 100,
                'total_material_cost' => rand(1000, 10000) / 100,
                'batch_number' => 'LOT-'.str_pad($i + 1, 3, '0', STR_PAD_LEFT),
            ]);
        }
    }
}
