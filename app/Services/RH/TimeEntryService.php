<?php

namespace App\Services\RH;

use App\Enums\RH\TimeEntryStatus;
use App\Models\RH\TimeEntry;
use Exception;

class TimeEntryService
{
    /**
     * Soumet un pointage pour validation.
     * @throws Exception
     */
    public function submit(TimeEntry $entry): void
    {
        // Compare la valeur du statut de l'entrée avec la valeur de l'énumération
        if ($entry->status->value === TimeEntryStatus::APPROVED->value) {
            throw new Exception('Impossible de modifier un pointage déjà approuvé.');
        }

        // Met à jour le statut en utilisant la valeur de l'énumération
        $entry->update(['status' => TimeEntryStatus::SUBMITTED->value]);
    }

    /**
     * Approuve un pointage pour la paie et l'analytique.
     */
    public function approve(TimeEntry $entry): void
    {
        $entry->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by_id' => auth()->id(),
        ]);
    }

    /**
     * Refuse un pointage avec un motif.
     */
    public function refuse(TimeEntry $entry, string $reason): void
    {
        $entry->update([
            'status' => 'draft', // Repasse en brouillon pour correction
            'refusal_reason' => $reason,
        ]);
    }
}
