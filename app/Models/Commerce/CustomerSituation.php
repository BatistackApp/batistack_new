<?php

namespace App\Models\Commerce;

use App\Models\Chantiers\Chantier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSituation extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_quote_id',
        'chantier_id',
        'number',
        'status',
        'total_ht',
        'total_ttc',
        'retenue_garantie_amount',
        'prorata_amount',
        'approved_at',
    ];

    public function customerQuote(): BelongsTo
    {
        return $this->belongsTo(CustomerQuote::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    protected function casts(): array
    {
        return [
            'approved_at' => 'timestamp',
        ];
    }
}
