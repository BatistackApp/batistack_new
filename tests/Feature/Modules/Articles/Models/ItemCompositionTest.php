<?php

use App\Models\Articles\Item;
use App\Models\Articles\ItemComposition;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('tests item composition relations', function () {
    $parent = Item::factory()->create();
    $child = Item::factory()->create();
    
    $composition = ItemComposition::create([
        'parent_item_id' => $parent->id,
        'child_item_id' => $child->id,
        'quantity' => 2.5,
        'loss_percentage' => 10.0,
    ]);
    
    expect($composition->childItem)->toBeInstanceOf(Item::class)
        ->and($composition->childItem->id)->toBe($child->id);
});
