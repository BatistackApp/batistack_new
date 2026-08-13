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
        'parent_id',
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

    /**
     * Le modèle parent (version précédente)
     */
    public function parent()
    {
        return $this->belongsTo(BimModel::class, 'parent_id');
    }

    /**
     * Les modèles enfants (versions suivantes)
     */
    public function children()
    {
        return $this->hasMany(BimModel::class, 'parent_id');
    }
}
