<?php

namespace App\Notifications\Tiers;

use App\Enums\Tiers\LegalStatus;
use App\Models\Tiers\ThirdParty;
use Filament\Actions\Action;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class LegalStatusChangedNotification extends Notification
{
    public function __construct(
        protected ThirdParty $thirdParty,
        protected ?LegalStatus $oldStatus,
        protected LegalStatus $newStatus
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $oldLabel = $this->oldStatus?->getLabel() ?? 'Non vérifié';

        return (new MailMessage)
            ->subject("Alerte changement de statut juridique : {$this->thirdParty->name}")
            ->greeting('Bonjour,')
            ->line("Le statut juridique du tiers {$this->thirdParty->name} a changé.")
            ->line("Ancien statut : {$oldLabel}")
            ->line("Nouveau statut : {$this->newStatus->getLabel()}")
            ->line("SIREN : {$this->thirdParty->siren}")
            ->action('Gérer le tiers', route('filament.tiers.resources.third-parties.edit', ['record' => $this->thirdParty]))
            ->line('Veuillez vérifier la situation de ce tiers et adapter vos actions en conséquence.');
    }

    public function toDatabase($notifiable): array
    {
        $oldLabel = $this->oldStatus?->getLabel() ?? 'Non vérifié';

        return \Filament\Notifications\Notification::make()
            ->danger()
            ->title("Alerte changement de statut juridique : {$this->thirdParty->name}")
            ->body("Le statut juridique est passé de « {$oldLabel} » à « {$this->newStatus->getLabel()} ».")
            ->actions([
                Action::make('show')
                    ->label('Gérer le tiers')
                    ->icon(Phosphor::Eye)
                    ->url(route('filament.tiers.resources.third-parties.edit', ['record' => $this->thirdParty])),
            ])
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        $oldLabel = $this->oldStatus?->getLabel() ?? 'Non vérifié';

        return [
            'third_party_id' => $this->thirdParty->id,
            'title' => "Changement de statut juridique : {$this->thirdParty->name}",
            'message' => "Statut passé de « {$oldLabel} » à « {$this->newStatus->getLabel()} »",
            'icon' => Phosphor::WarningCircle,
            'color' => 'danger',
        ];
    }
}
