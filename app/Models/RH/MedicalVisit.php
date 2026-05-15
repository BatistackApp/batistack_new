<?php

namespace App\Models\RH;

use App\Enums\RH\MedicalAptitude;
use App\Enums\RH\MedicalVisiteType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MedicalVisit extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'employee_id',
        'type',
        'visit_date',
        'next_due_date',
        'aptitude',
        'practitioner_name',
        'notes',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected function casts(): array
    {
        return [
            'type' => MedicalVisiteType::class,
            'aptitude' => MedicalAptitude::class,
            'visit_date' => 'date',
            'next_due_date' => 'date',
        ];
    }

    /**
     * Vérifie si la visite est périmée.
     */
    #[Scope]
    public function isExpired(): bool
    {
        return $this->next_due_date && $this->next_due_date->isPast();
    }
}
