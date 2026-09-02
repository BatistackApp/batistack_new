<?php

namespace App\Models\Commerce\Concerns;

trait DeletableWhenDraft
{
    public function canBeDeleted(): bool
    {
        return $this->status->value === 'draft';
    }
}
