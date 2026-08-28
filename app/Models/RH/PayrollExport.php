<?php

namespace App\Models\RH;

use App\Enums\RH\PayrollExportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'month',
        'year',
        'status',
        'total_employees',
    ];

    protected function casts(): array
    {
        return [
            'status' => PayrollExportStatus::class,
        ];
    }

    public function variables(): HasMany
    {
        return $this->hasMany(PayrollVariable::class);
    }
}
