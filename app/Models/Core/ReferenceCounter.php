<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class ReferenceCounter extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'year',
        'prefix',
        'last_number',
    ];
}
