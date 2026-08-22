<?php

namespace App\Models\Vision3D;

use App\Observers\Vision3D\BimModelObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy([BimModelObserver::class])]
class BimModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'file_path',
        'format',
        'file_size',
        'thumbnail_path',
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
     * Les quantitatifs (BOM) extraits de cette maquette
     */
    public function quantities()
    {
        return $this->hasMany(BimQuantity::class);
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
