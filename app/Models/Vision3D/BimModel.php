<?php

namespace App\Models\Vision3D;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BimModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'file_path',
        'format',
        'file_size',
        'version',
        'modelable_id',
        'modelable_type',
    ];

    /**
     * L'entité à laquelle cette maquette est rattachée (Chantier, Article, etc.)
     */
    public function modelable()
    {
        return $this->morphTo();
    }

    /**
     * Les annotations / punaises posées sur cette maquette
     */
    public function annotations()
    {
        return $this->hasMany(BimAnnotation::class);
    }
}
