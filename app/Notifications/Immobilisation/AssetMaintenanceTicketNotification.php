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

        $body = $asset
            ? "Un outil a été déclaré en casse : {$this->assetLabel($asset)} (".($asset->serial_number ?? 'N/A').').'
            : 'Un outil a été déclaré en casse (actif supprimé).';

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

    protected function assetLabel(mixed $asset): string
    {
        if (method_exists($asset, 'getLabel')) {
            return (string) $asset->getLabel();
        }

        return $asset->name ?? '—';
    }
}
