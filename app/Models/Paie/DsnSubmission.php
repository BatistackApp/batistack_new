<?php

namespace App\Models\Paie;

use App\Enums\Paie\DsnSubmissionStatus;
use App\Models\Core\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class DsnSubmission extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id',
        'period',
        'status',
        'export_type',
        'submitted_at',
        'exported_at',
        'error_message',
        'exported_file_path',
        'payslips_count',
        'total_gross',
        'total_net',
        'total_employer_cost',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DsnSubmissionStatus::class,
            'submitted_at' => 'datetime',
            'exported_at' => 'datetime',
            'payslips_count' => 'integer',
            'total_gross' => 'decimal:2',
            'total_net' => 'decimal:2',
            'total_employer_cost' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DsnSubmissionLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payslips()
    {
        return $this->belongsToMany(Payslip::class, 'dsn_submission_lines')
            ->withPivot('status', 'error_message')
            ->withTimestamps();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['period', 'status', 'export_type', 'payslips_count', 'total_gross'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
