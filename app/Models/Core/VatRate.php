<?php

namespace App\Models\Core;

use App\Observers\Core\VatRateObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([VatRateObserver::class])]
class VatRate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'rate', 'is_default', 'is_active'];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];
}
