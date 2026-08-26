<?php

namespace App\Models\Gpao;

use App\Models\User;
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
        return $this->belongsTo(ManufacturingOrder::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}
