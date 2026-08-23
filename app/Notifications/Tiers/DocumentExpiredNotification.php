<?php

namespace App\Notifications\Tiers;

use App\Models\Tiers\ThirdPartyDocument;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;

class DocumentExpiredNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ThirdPartyDocument $document
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $type = $this->document->type->getLabel();
        $tiersName = $this->document->thirdParty->name;

        return (new MailMessage)
            ->subject("Document expiré — {$tiersName}")
            ->greeting('Bonjour,')
            ->error()
            ->line("Le document \"{$type}\" du tiers \"{$tiersName}\" est expiré depuis le ".$this->document->expiration_date->format('d/m/Y').'.')
            ->action('Gérer le tiers', route('filament.tiers.resources.third-parties.edit', ['record' => $this->document->thirdParty]))
            ->line('Veuillez renouveler ce document immédiatement pour éviter tout blocage.');
    }

    public function toDatabase(object $notifiable): array
    {
        return Notification::make()
            ->danger()
            ->title("Document expiré : {$this->document->type->getLabel()}")
            ->body("Le document \"{$this->document->type->label()}\" de \"{$this->document->thirdParty->name}\" est expiré depuis le ".$this->document->expiration_date->format('d/m/Y').'.')
            ->actions([
                Action::make('manage')
                    ->label('Gérer le tiers')
                    ->url(route('filament.tiers.resources.third-parties.edit', ['record' => $this->document->thirdParty])),
            ])
            ->getDatabaseMessage();
    }

    public function toArray(object $notifiable): array
    {
        return [
            'third_party_document_id' => $this->document->id,
            'third_party_id' => $this->document->third_party_id,
            'title' => "Document expiré : {$this->document->type->getLabel()}",
            'message' => "Expiré le {$this->document->expiration_date->format('d/m/Y')}.",
        ];
    }
}
