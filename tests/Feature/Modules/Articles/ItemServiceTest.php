<?php

use App\Models\Articles\Item;
use App\Enums\Articles\ItemType;
use App\Services\Articles\ItemService;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;

beforeEach(function () {
    $this->itemService = app(ItemService::class);
    $this->unit = Unit::factory()->create(['symbol' => 'u']);
    $this->vat = VatRate::factory()->create(['rate' => 20]);
});
