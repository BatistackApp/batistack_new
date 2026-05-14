<?php

namespace App\Services\RH;

use App\Models\RH\TimeEntry;
use Exception;

class TimeEntryService
{
    /**
     * Soumet un pointage pour validation.
     */
    public function submit(TimeEntry $entry): void
    {
        if ($entry->status === 'approved') {
            throw new Exception('Impossible de modifier un pointage déjà approuvé.');
        }

        $entry->update(['status' => 'submitted']);
    }

    /**
     * Approuve un pointage pour la paie et l'analytique.
     */
    public function approve(TimeEntry $entry): void
    {
        $entry->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
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
