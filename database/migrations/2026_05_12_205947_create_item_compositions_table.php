<?php

use App\Models\Articles\Item;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_compositions', function (Blueprint $table) {
            $table->id();
            $table->decimal('quantity', 15, 4);
            $table->decimal('loss_percentage', 15, 4)->default(0);
            $table->foreignIdFor(Item::class, 'parent_item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignIdFor(Item::class, 'child_item_id')->constrained('items')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_compositions');
    }
};
