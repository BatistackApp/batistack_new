<?php

namespace App\Models\Gpao;

use Illuminate\Database\Eloquent\Model;

class QualityCheck extends Model
{
    protected $fillable = [
        'manufacturing_order_id',
        'inspector_id',
        'status',
        'notes',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function manufacturingOrder()
    {
        return $this->belongsTo(\App\Models\Gpao\ManufacturingOrder::class);
    }

    public function inspector()
    {
        return $this->belongsTo(\App\Models\User::class, 'inspector_id');
    }
}
