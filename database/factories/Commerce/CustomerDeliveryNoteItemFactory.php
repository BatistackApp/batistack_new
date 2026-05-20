<?php

namespace Database\Factories\Commerce;

use App\Models\Articles\Item;
use App\Models\Commerce\CustomerDeliveryNote;
use App\Models\Commerce\CustomerDeliveryNoteItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CustomerDeliveryNoteItemFactory extends Factory
{
    protected $model = CustomerDeliveryNoteItem::class;

    public function definition(): array
    {
        return [
            'quantity_delivered' => $this->faker->randomFloat(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'customer_delivery_note_id' => CustomerDeliveryNote::factory(),
            'item_id' => Item::factory(),
        ];
    }
}
