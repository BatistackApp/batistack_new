<?php

namespace App\Models\Commerce;

use App\Models\Chantiers\Chantier;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'chantier_id',
        'reference',
        'status',
        'total_ht',
        'total_ttc',
        'signed_at',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ThirdParty::class, 'customer_id');
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
        ];
    }
}
