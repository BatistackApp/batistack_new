<?php

namespace App\Models\Commerce;

use App\Enums\Commerce\PaymentMethod;
use App\Enums\Commerce\PaymentStatus;
use App\Enums\Commerce\PaymentType;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'third_party_id',
        'reference',
        'type',
        'method',
        'status',
        'amount',
        'payment_date',
        'notes',
    ];

    public function thirdParty(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    protected function casts(): array
    {
        return [
            'type' => PaymentType::class,
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }
}
