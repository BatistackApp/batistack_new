<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountingSync extends Model
{
    protected $fillable = [
        'syncable_type',
        'syncable_id',
        'status',
        'attempts',
        'last_error',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    public function syncable(): MorphTo
    {
        return $this->morphTo();
    }
}
