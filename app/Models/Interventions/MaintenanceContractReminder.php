<?php

namespace App\Models\Interventions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceContractReminder extends Model
{
    protected $table = 'maintenance_contract_reminders';

    protected $fillable = [
        'contract_id',
        'due_date',
        'days_before',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(MaintenanceContract::class, 'contract_id');
    }
}
