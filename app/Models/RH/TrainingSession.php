<?php

namespace App\Models\RH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Enums\RH\TrainingSessionStatus;
use App\Enums\RH\OpcoStatus;
use App\Enums\RH\QualificationType;
use App\Enums\RH\CertificationSymbol;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TrainingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'started_at',
        'ended_at',
        'status',
        'cost',
        'opco_reimbursement',
        'opco_status',
        'qualification_type',
        'certification_symbol',
        'validity_months',
    ];

    protected $casts = [
        'started_at' => 'date',
        'ended_at' => 'date',
        'status' => TrainingSessionStatus::class,
        'opco_status' => OpcoStatus::class,
        'qualification_type' => QualificationType::class,
        'certification_symbol' => CertificationSymbol::class,
        'cost' => 'decimal:2',
        'opco_reimbursement' => 'decimal:2',
    ];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_training_session')
            ->withPivot('status')
            ->withTimestamps();
    }
}
