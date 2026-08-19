<?php

namespace App\Models\Immobilisation;

use App\Models\Chantiers\Chantier;
use App\Models\User;
use Database\Factories\Immobilisation\FixedAssetAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAssetAssignment extends Model
{
    /** @use HasFactory<FixedAssetAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'fixed_asset_id',
        'chantier_id',
        'assigned_at',
        'released_at',
        'assigned_by',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
