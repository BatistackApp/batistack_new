<?php

namespace App\Models\Core;

use App\Enums\Core\UnitType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'symbol', 'type', 'is_active'];

    protected $casts = [
        'type' => UnitType::class,
        'is_active' => 'boolean',
    ];
}
