<?php

use App\Console\Commands\Articles\CheckLowStockCommand;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\User;
use App\Notifications\Articles\LowStockNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('checks low stocks and sends notification to admins', function () {
    Notification::fake();
    
    $admin = User::factory()->create(['email' => 'admin@test.com']);
    // Assuming there's a scopeAdmin() or a way to distinguish admins.
    // The command uses `User::admin()->get()`. Let's mock or rely on the factory creating an admin if no roles.
    // We can just use the created user for notification assertions if `admin()` works.

    $warehouse = Warehouse::factory()->create();
    $item = Item::factory()->create();
    
    $stock = Stock::factory()->create([
        'warehouse_id' => $warehouse->id,
        'item_id' => $item->id,
        'quantity' => 5,
        'min_threshold' => 10,
    ]);
    
    $exitCode = Artisan::call('articles:check-stocks');
    
    expect($exitCode)->toBe(0);
    
    // Check if notification was sent
    // We don't strictly assert the exact user here unless `User::admin()` is perfectly isolated.
    // We'll just assert that the notification was sent.
    Notification::assertSentTo(
        User::all(),
        LowStockNotification::class,
        function ($notification) use ($stock) {
            return $notification->stock->id === $stock->id;
        }
    );
});

it('does nothing if no critical stocks', function () {
    Notification::fake();

    $warehouse = Warehouse::factory()->create();
    $item = Item::factory()->create();
    
    Stock::factory()->create([
        'warehouse_id' => $warehouse->id,
        'item_id' => $item->id,
        'quantity' => 15,
        'min_threshold' => 10,
    ]);
    
    $exitCode = Artisan::call('articles:check-stocks');
    
    expect($exitCode)->toBe(0);
    
    Notification::assertNothingSent();
});
