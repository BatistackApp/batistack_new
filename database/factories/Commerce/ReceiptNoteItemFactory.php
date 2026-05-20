<?php

namespace Database\Factories\Commerce;

use App\Models\Commerce\PurchaseOrderItem;
use App\Models\Commerce\ReceiptNote;
use App\Models\Commerce\ReceiptNoteItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ReceiptNoteItemFactory extends Factory
{
    protected $model = ReceiptNoteItem::class;

    public function definition(): array
    {
        return [
            'quantity_received' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'receipt_note_id' => ReceiptNote::factory(),
            'purchase_order_item_id' => PurchaseOrderItem::factory(),
        ];
    }
}
