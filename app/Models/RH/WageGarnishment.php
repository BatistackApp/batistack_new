<?php

namespace App\Models\RH;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WageGarnishment extends Model
{
    protected $fillable = [
        'employee_id',
        'reference',
        'total_amount_due',
        'amount_collected',
        'monthly_deduction',
        'start_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'is_active' => 'boolean',
            'total_amount_due' => 'decimal:2',
            'amount_collected' => 'decimal:2',
            'monthly_deduction' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Calcule la retenue sur salaire (SATD).
     * Si une mensualité est forcée, elle est retournée.
     * Sinon, applique un barème légal simplifié.
     */
    public function calculateDeduction(float $netSalary): float
    {
        $remainingDue = max(0, $this->total_amount_due - $this->amount_collected);

        if ($this->monthly_deduction !== null) {
            return (float) max(0, min(max(0, $this->monthly_deduction), $remainingDue));
        }

        // Barème légal simplifié (Tranches 2024 indicatives, hors charges de famille)
        // 1/10 sur la tranche < 434
        // 1/5 sur la tranche 434 - 852
        // 1/4 sur la tranche 852 - 1276
        // 1/3 sur la tranche 1276 - 1694
        // 2/3 sur la tranche 1694 - 2107
        // 100% au-delà de 2107
        // + RSA insaisissable (environ 635€)

        $saisissable = 0;

        if ($netSalary <= 635) {
            return 0; // RSA insaisissable
        }

        if ($netSalary > 0) {
            $saisissable += min($netSalary, 434) / 10;
        }
        if ($netSalary > 434) {
            $saisissable += min($netSalary - 434, 852 - 434) / 5;
        }
        if ($netSalary > 852) {
            $saisissable += min($netSalary - 852, 1276 - 852) / 4;
        }
        if ($netSalary > 1276) {
            $saisissable += min($netSalary - 1276, 1694 - 1276) / 3;
        }
        if ($netSalary > 1694) {
            $saisissable += min($netSalary - 1694, 2107 - 1694) * 2 / 3;
        }
        if ($netSalary > 2107) {
            $saisissable += ($netSalary - 2107);
        }

        // Protéger le RSA
        $maxSaisissable = max(0, $netSalary - 635);
        $saisissable = min($saisissable, $maxSaisissable);

        return round(max(0, min($saisissable, $remainingDue)), 2);
    }
}
