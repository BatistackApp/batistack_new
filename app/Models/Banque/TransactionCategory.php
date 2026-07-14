<?php

namespace App\Models\Banque;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'type',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(CategorizationRule::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }
}
