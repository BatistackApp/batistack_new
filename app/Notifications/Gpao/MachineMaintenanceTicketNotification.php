<?php

namespace App\Notifications\Gpao;

use App\Models\Gpao\MachineMaintenanceTicket;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class MachineMaintenanceTicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected MachineMaintenanceTicket $ticket) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $machine = $this->ticket->machine;

        $body = $machine
            ? "Maintenance préventive requise pour la machine « {$machine->name} » ({$machine->reference})."
            : 'Ticket de maintenance créé (machine supprimée).';

        return \Filament\Notifications\Notification::make()
            ->color('warning')
            ->title('Ticket de maintenance machine')
            ->body($body)
            ->icon(Phosphor::Wrench)
            ->actions([
                Action::make('ticket_view')
                    ->label('Voir le ticket')
                    ->url(url('/gpao/machine-maintenance-tickets/'.$this->ticket->getKey())),
            ])
            ->getDatabaseMessage();
    }
}
