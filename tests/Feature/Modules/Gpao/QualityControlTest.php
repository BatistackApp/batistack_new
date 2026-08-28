<?php

use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Articles\Item;
use App\Models\Articles\Warehouse;
use App\Models\Gpao\ManufacturingOrder;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->item = Item::factory()->create();
    $this->warehouse = Warehouse::factory()->create();

    $this->manufacturingOrder = ManufacturingOrder::create([
        'reference' => 'OF-QC-TEST',
        'item_id' => $this->item->id,
        'quantity_planned' => 10,
        'status' => ManufacturingStatus::IN_PROGRESS,
    ]);
});

it('can transition an order to quality control', function () {
    $this->manufacturingOrder->update(['status' => ManufacturingStatus::QUALITY_CONTROL]);

    expect($this->manufacturingOrder->fresh()->status)->toBe(ManufacturingStatus::QUALITY_CONTROL);
});

it('can fail quality control and return to in progress', function () {
    $user = User::factory()->create();
    actingAs($user);

    $this->manufacturingOrder->update(['status' => ManufacturingStatus::QUALITY_CONTROL]);

    $this->manufacturingOrder->qualityChecks()->create([
        'inspector_id' => $user->id,
        'status' => 'failed',
        'notes' => 'Defects found',
        'checked_at' => now(),
    ]);

    $this->manufacturingOrder->update(['status' => ManufacturingStatus::IN_PROGRESS]);

    expect($this->manufacturingOrder->fresh()->status)->toBe(ManufacturingStatus::IN_PROGRESS);

    $qualityCheck = $this->manufacturingOrder->qualityChecks()->first();
    expect($qualityCheck)->not->toBeNull()
        ->and($qualityCheck->status)->toBe('failed');
});
