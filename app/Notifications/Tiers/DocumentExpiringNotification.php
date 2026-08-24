<?php

namespace App\Notifications\Tiers;

use App\Models\Tiers\ThirdPartyDocument;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as BaseNotification;

class DocumentExpiringNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ThirdPartyDocument $document,
        protected int $daysRemaining
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
            ->subject("Document expirant dans {$this->daysRemaining} jours — {$tiersName}")
            ->greeting('Bonjour,')
            ->line("Le document \"{$type}\" du tiers \"{$tiersName}\" expire dans {$this->daysRemaining} jours.")
            ->line("Date d'expiration : ".$this->document->expiration_date->format('d/m/Y'))
            ->action('Gérer le tiers', route('filament.tiers.resources.third-parties.edit', ['record' => $this->document->thirdParty]))
            ->line('Merci de renouveler ce document dans les plus brefs délais.');
    }

    public function toDatabase(object $notifiable): array
    {
        return Notification::make()
            ->warning()
            ->title("Document expirant : {$this->document->type->getLabel()}")
            ->body("Le document \"{$this->document->type->getLabel()}\" de \"{$this->document->thirdParty->name}\" expire dans {$this->daysRemaining} jours.")
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
            'title' => "Document expirant : {$this->document->type->getLabel()}",
            'message' => "Expire dans {$this->daysRemaining} jours ({$this->document->expiration_date->format('d/m/Y')}).",
            'days_remaining' => $this->daysRemaining,
        ];
    }
}
