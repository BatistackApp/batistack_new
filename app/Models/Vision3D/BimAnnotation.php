<?php

namespace App\Models\Vision3D;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BimAnnotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'bim_model_id',
        'title',
        'description',
        'position_x',
        'position_y',
        'position_z',
        'camera_x',
        'camera_y',
        'camera_z',
        'target_id',
        'target_type',
    ];

    /**
     * La maquette sur laquelle cette annotation est posée
     */
    public function bimModel()
    {
        return $this->belongsTo(BimModel::class);
    }

    /**
     * La cible de cette annotation (ex: une Intervention liée)
     */
    public function target()
    {
        return $this->morphTo();
    }
}
