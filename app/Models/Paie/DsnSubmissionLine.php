<?php

namespace App\Models\Paie;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DsnSubmissionLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'dsn_submission_id',
        'payslip_id',
        'status',
        'error_message',
    ];

    public function dsnSubmission(): BelongsTo
    {
        return $this->belongsTo(DsnSubmission::class);
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }
}
