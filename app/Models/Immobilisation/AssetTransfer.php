<?php

namespace App\Models\Immobilisation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Chantiers\Chantier;
use App\Models\User;

class AssetTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixed_asset_id',
        'from_chantier_id',
        'to_chantier_id',
        'requested_by',
        'transfer_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
        ];
    }

    public function fixedAsset(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function fromChantier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Chantier::class, 'from_chantier_id');
    }

    public function toChantier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Chantier::class, 'to_chantier_id');
    }

    public function requester(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
