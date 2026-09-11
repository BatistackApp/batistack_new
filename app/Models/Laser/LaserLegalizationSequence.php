<?php

namespace App\Models\Laser;

use Illuminate\Database\Eloquent\Model;

class LaserLegalizationSequence extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'last_hash',
        'last_reference',
    ];
}
