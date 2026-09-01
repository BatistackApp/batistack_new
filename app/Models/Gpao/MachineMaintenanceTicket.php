<?php

namespace App\Models\Gpao;

use App\Enums\Gpao\MachineMaintenanceTicketStatus;
use App\Enums\Gpao\MachineMaintenanceTicketType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineMaintenanceTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_id',
        'type',
        'status',
        'description',
        'cost_ht',
        'provider_name',
        'notes',
        'reported_by_id',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MachineMaintenanceTicketStatus::class,
            'type' => MachineMaintenanceTicketType::class,
            'cost_ht' => 'decimal:2',
            'resolved_at' => 'datetime',
        ];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_id');
    }
}
