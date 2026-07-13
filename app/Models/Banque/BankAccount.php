<?php

namespace App\Models\Banque;

use App\Enums\Banque\BankAccountType;
use App\Models\Core\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'iban',
        'bic',
        'currency',
        'balance',
        'bankin_item_id',
    ];

    protected $casts = [
        'type' => BankAccountType::class,
        'balance' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }
}
