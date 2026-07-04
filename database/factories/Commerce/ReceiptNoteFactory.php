<?php

namespace Database\Factories\Commerce;

use App\Models\Articles\Warehouse;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Commerce\ReceiptNote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ReceiptNoteFactory extends Factory
{
    protected $model = ReceiptNote::class;

    public function definition(): array
    {
        return [
            'reference' => $this->faker->word(),
            'status' => \App\Enums\Commerce\DeliveryStatus::PREPARATION,
            'received_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'purchase_order_id' => PurchaseOrder::factory(),
            'warehouse_id' => Warehouse::factory(),
        ];
    }
}
