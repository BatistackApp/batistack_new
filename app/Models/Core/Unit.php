<?php

namespace App\Models\Core;

use App\Enums\Core\UnitType;
use App\Observers\Core\UnitObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([UnitObserver::class])]
class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'symbol', 'type', 'is_active'];

    protected $casts = [
        'type' => UnitType::class,
        'is_active' => 'boolean',
    ];
}
