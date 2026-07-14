<?php

namespace App\Models\Banque;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategorizationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_category_id',
        'keyword',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'transaction_category_id');
    }
}
