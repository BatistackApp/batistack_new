<?php

namespace App\Models\RH;

use App\Enums\RH\EquipementType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Equipement extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'type',
        'label',
        'brand',
        'model_name',
        'serial_number',
        'assigned_at',
        'expires_at',
        'last_check_at',
        'notes',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected function casts(): array
    {
        return [
            'assigned_at' => 'date',
            'expires_at' => 'date',
            'last_check_at' => 'date',
            'type' => EquipementType::class,
        ];
    }

    /**
     * Détermine si l'équipement est périmé (crucial pour les EPI).
     */
    #[Scope]
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
