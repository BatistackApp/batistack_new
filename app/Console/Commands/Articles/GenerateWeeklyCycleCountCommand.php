<?php

namespace App\Console\Commands\Articles;

use App\Models\Articles\Warehouse;
use App\Services\Articles\CycleCountingService;
use Illuminate\Console\Command;

class GenerateWeeklyCycleCountCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:generate-cycle-counts {--items=10 : Number of items to count}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate weekly cycle counts for all warehouses';

    /**
     * Execute the console command.
     */
    public function handle(CycleCountingService $service)
    {
        $this->info('Starting generation of inventory cycle counts...');

        $warehouses = Warehouse::all();
        $itemsCount = (int) $this->option('items');

        foreach ($warehouses as $warehouse) {
            $this->info("Generating cycle count for warehouse: {$warehouse->name}");
            $cycle = $service->generateCycle($warehouse, $itemsCount);
            $this->line("Created cycle #{$cycle->id} for {$warehouse->name}");
        }

        $this->info('Done.');
    }
}
