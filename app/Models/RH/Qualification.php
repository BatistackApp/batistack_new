<?php

namespace App\Models\RH;

use App\Enums\RH\CertificationSymbol;
use App\Enums\RH\QualificationType;
use App\Observers\RH\QualificationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([QualificationObserver::class])]
class Qualification extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'employee_id',
        'type',
        'label',
        'reference_number',
        'obtained_at',
        'expires_at',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected function casts(): array
    {
        return [
            'type' => QualificationType::class,
            'obtained_at' => 'date',
            'expires_at' => 'date',
            'label' => CertificationSymbol::class,
        ];
    }
}
