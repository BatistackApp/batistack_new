<?php

namespace App\Notifications\Immobilisation;

use App\Models\Immobilisation\AssetMaintenanceTicket;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class AssetMaintenanceTicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected AssetMaintenanceTicket $ticket) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $asset = $this->ticket->asset;

        $body = "Un outil a été déclaré en casse : {$asset->name} (".($asset->serial_number ?? 'N/A').').';

        return \Filament\Notifications\Notification::make()
            ->color('danger')
            ->title('Déclaration de casse : '.$this->ticket->reference)
            ->body($body)
            ->icon(Phosphor::Warning)
            ->actions([
                Action::make('ticket_view')
                    ->label('Voir le ticket')
                    ->url(url('/immobilisations/asset-maintenance-tickets/'.$this->ticket->getKey())),
            ])
            ->getDatabaseMessage();
    }
}
