<?php

namespace App\Models\Paie;

use App\Enums\Paie\SalaryPaymentStatus;
use App\Models\Banque\BankAccount;
use App\Models\Core\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class SalaryPaymentRun extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'bank_account_id',
        'period',
        'total_amount',
        'count',
        'status',
        'idempotency_key',
        'bridge_payment_request_id',
        'consent_url',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'count' => 'integer',
            'status' => SalaryPaymentStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalaryPaymentLine::class, 'run_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['period', 'total_amount', 'count', 'status', 'bridge_payment_request_id', 'consent_url'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
