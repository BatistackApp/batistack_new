<?php

namespace App\Models\Immobilisation;

use App\Models\Chantiers\Chantier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function fromChantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class, 'from_chantier_id');
    }

    public function toChantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class, 'to_chantier_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
