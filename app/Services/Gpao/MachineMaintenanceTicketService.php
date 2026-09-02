<?php

namespace App\Services\Gpao;

use App\Enums\Gpao\MachineMaintenanceTicketStatus;
use App\Enums\Gpao\MachineStatus;
use App\Models\Gpao\MachineMaintenanceTicket;
use App\Models\User;
use App\Notifications\Gpao\MachineMaintenanceTicketNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class MachineMaintenanceTicketService
{
    public function start(MachineMaintenanceTicket $ticket): void
    {
        $this->assertStatus($ticket, [MachineMaintenanceTicketStatus::OPEN]);

        $ticket->update(['status' => MachineMaintenanceTicketStatus::IN_PROGRESS]);
    }

    public function resolve(
        MachineMaintenanceTicket $ticket,
        ?float $costHt = null,
        ?string $provider = null,
        ?string $notes = null,
    ): void {
        $this->assertStatus($ticket, [
            MachineMaintenanceTicketStatus::OPEN,
            MachineMaintenanceTicketStatus::IN_PROGRESS,
        ]);

        DB::transaction(function () use ($ticket, $costHt, $provider, $notes) {
            $ticket->update([
                'status' => MachineMaintenanceTicketStatus::RESOLVED,
                'resolved_at' => now(),
                'cost_ht' => $costHt,
                'provider_name' => $provider,
                'notes' => $notes ?? $ticket->notes,
            ]);

            $this->restoreMachineStatus($ticket);
        });
    }

    public function cancel(MachineMaintenanceTicket $ticket): void
    {
        $this->assertStatus($ticket, [
            MachineMaintenanceTicketStatus::OPEN,
            MachineMaintenanceTicketStatus::IN_PROGRESS,
        ]);

        DB::transaction(function () use ($ticket) {
            $ticket->update(['status' => MachineMaintenanceTicketStatus::CANCELED]);

            $this->restoreMachineStatus($ticket);
        });
    }

    public function notifyAdmins(MachineMaintenanceTicket $ticket): void
    {
        $admins = User::where('is_admin', true)->get();

        Notification::send($admins, new MachineMaintenanceTicketNotification($ticket));
    }

    protected function restoreMachineStatus(MachineMaintenanceTicket $ticket): void
    {
        $machine = $ticket->machine;

        if (! $machine) {
            return;
        }

        $hasOpenTicket = MachineMaintenanceTicket::where('machine_id', $machine->id)
            ->whereIn('status', [
                MachineMaintenanceTicketStatus::OPEN,
                MachineMaintenanceTicketStatus::IN_PROGRESS,
            ])
            ->where('id', '!=', $ticket->id)
            ->exists();

        if (! $hasOpenTicket && $machine->status === MachineStatus::MAINTENANCE) {
            $machine->update(['status' => MachineStatus::OPERATIONAL]);
        }
    }

    protected function assertStatus(MachineMaintenanceTicket $ticket, array $allowed): void
    {
        if (! in_array($ticket->status, $allowed, true)) {
            throw new \LogicException('Transition de statut non autorisée depuis « '.$ticket->status->getLabel().' ».');
        }
    }
}
