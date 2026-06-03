<?php

namespace App\Models\Flottes;

use App\Models\Articles\Item;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'item_id',
        'serial_number',
        'quantity',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    // ============ SCOPES ============

    public function scopeByVehicle(Builder $query, int $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeByItem(Builder $query, int $itemId): Builder
    {
        return $query->where('item_id', $itemId);
    }

    public function scopeWithSerialNumber(Builder $query): Builder
    {
        return $query->whereNotNull('serial_number');
    }

    public function scopeWithoutSerialNumber(Builder $query): Builder
    {
        return $query->whereNull('serial_number');
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->whereHas('item', function ($q) use ($category) {
            $q->where('category', 'ilike', "%{$category}%");
        });
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('serial_number', 'ilike', "%{$search}%")
            ->orWhereHas('item', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%");
            });
    }

    // ============ METHODS ============

    public function hasSerialNumber(): bool
    {
        return $this->serial_number !== null;
    }

    public function getDisplayName(): string
    {
        return $this->item->name ?? 'Unknown Item';
    }

    public function getItemReference(): string
    {
        return $this->item->reference ?? '';
    }

    public function getTotalValue(): float
    {
        $unitPrice = $this->item->unit_price ?? 0;
        return (float) $unitPrice * (float) $this->quantity;
    }

    public function isTracked(): bool
    {
        return $this->hasSerialNumber();
    }

    public function getQuantityLabel(): string
    {
        $unit = $this->item->unit ?? 'unité';
        return "{$this->quantity} {$unit}";
    }

    public function updateQuantity(int $newQuantity): void
    {
        $this->update(['quantity' => $newQuantity]);
    }

    public function incrementQuantity(int $amount = 1): void
    {
        $this->update(['quantity' => $this->quantity + $amount]);
    }

    public function decrementQuantity(int $amount = 1): void
    {
        $newQuantity = max(0, $this->quantity - $amount);
        $this->update(['quantity' => $newQuantity]);
    }
}
