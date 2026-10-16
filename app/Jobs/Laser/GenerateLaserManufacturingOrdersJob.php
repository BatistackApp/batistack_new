<?php

namespace App\Jobs\Laser;

use App\Enums\Articles\ItemType;
use App\Enums\Core\UnitType;
use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Articles\Item;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use App\Models\Gpao\ManufacturingOrder;
use App\Models\Laser\LaserOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateLaserManufacturingOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public LaserOrder $order
    ) {}

    public function handle(): void
    {
        $this->order->load('lines.material');

        $defaultUnit = Unit::firstOrCreate(
            ['symbol' => 'u'],
            ['name' => 'Unité', 'type' => UnitType::UNIT, 'is_active' => true]
        );
        $defaultVatRate = VatRate::firstOrCreate(
            ['rate' => 20],
            ['name' => 'TVA 20%']
        );

        foreach ($this->order->lines as $line) {
            $item = $this->resolveOrCreateItem($line->material, $defaultUnit, $defaultVatRate);

            ManufacturingOrder::create([
                'reference' => 'OF-LASER-'.$this->order->reference.'-'.$line->id,
                'item_id' => $item->id,
                'laser_order_id' => $this->order->id,
                'quantity_planned' => $line->quantity,
                'status' => ManufacturingStatus::PLANNED,
            ]);
        }
    }

    protected function resolveOrCreateItem($material, ?Unit $defaultUnit, ?VatRate $defaultVatRate): Item
    {
        $reference = 'LASER-'.$material->id;

        return Item::firstOrCreate(
            ['reference' => $reference],
            [
                'name' => $material->name,
                'type' => ItemType::WORK,
                'unit_id' => $defaultUnit?->id,
                'vat_rate_id' => $defaultVatRate?->id,
                'is_active' => true,
            ]
        );
    }
}
