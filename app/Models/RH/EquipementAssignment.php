<?php

namespace App\Models\RH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipementAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipement_id',
        'employee_id',
        'chantier_id',
        'assigned_at',
        'returned_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function equipement(): BelongsTo
    {
        return $this->belongsTo(Equipement::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Chantiers\Chantier::class, 'chantier_id');
    }

    /**
     * Calcule le nombre de jours calendaires d'immobilisation.
     */
    public function getDurationInDays(): int
    {
        if (! $this->assigned_at) {
            return 0;
        }

        $end = $this->returned_at ?? now();
        $days = $this->assigned_at->diffInDays($end);

        // Même si c'est rendu le même jour, on compte 1 jour.
        return max(1, (int) $days);
    }

    /**
     * Calcule le coût total d'immobilisation (Jours * Coût Journalier).
     */
    public function getImmobilizationCost(): float
    {
        $dailyCost = $this->equipement->daily_cost ?? 0;
        return $this->getDurationInDays() * $dailyCost;
    }
}
