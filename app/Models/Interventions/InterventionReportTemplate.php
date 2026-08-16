<?php

namespace App\Models\Interventions;

use App\Enums\Interventions\InterventionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterventionReportTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'intervention_type',
        'schema',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'intervention_type' => InterventionType::class,
            'schema' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }
}