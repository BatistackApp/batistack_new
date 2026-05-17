<?php

namespace App\Models\Flottes;

use App\Enums\Flottes\FleetExpenseType;
use App\Models\Chantiers\Chantier;
use App\Models\Core\VatRate;
use App\Models\RH\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FleetExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'employee_id',
        'chantier_id',
        'type',
        'reference',
        'amount_ht',
        'vat_rate_id',
        'amount_ttc',
        'merchant_name',
        'description',
        'spent_at',
        'is_suspicious',
        'suspicion_reason',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class);
    }

    protected function casts(): array
    {
        return [
            'spent_at' => 'datetime',
            'amount_ht' => 'decimal:4',
            'amount_ttc' => 'decimal:2',
            'is_suspicious' => 'boolean',
            'type' => FleetExpenseType::class,
        ];
    }
}
