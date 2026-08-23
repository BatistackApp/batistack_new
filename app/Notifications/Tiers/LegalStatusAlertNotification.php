<?php

namespace App\Notifications\Tiers;

use App\Enums\Tiers\LegalStatus;
use App\Models\Tiers\ThirdParty;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class LegalStatusAlertNotification extends Notification
{
    public function __construct(
        protected ThirdParty $thirdParty,
        protected ?LegalStatus $previousStatus,
        protected ?LegalStatus $newStatus
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $prevLabel = $this->previousStatus?->getLabel() ?? 'Non renseigné';
        $newLabel = $this->newStatus?->getLabel() ?? 'Inconnu';

        return (new MailMessage)
            ->subject("Alerte statut juridique : {$this->thirdParty->name}")
            ->greeting('Bonjour,')
            ->line("Le statut juridique du tiers {$this->thirdParty->name} a évolué vers une situation critique : {$newLabel} (précédemment : {$prevLabel}).")
            ->action('Voir le tiers', route('filament.tiers.resources.third-parties.edit', ['record' => $this->thirdParty]))
            ->line('Veuillez vérifier les engagements contractuels en cours et prendre les mesures nécessaires.');
    }

    public function toDatabase($notifiable): array
    {
        $newLabel = $this->newStatus?->getLabel() ?? 'Inconnu';

        return FilamentNotification::make()
            ->danger()
            ->title("Alerte statut juridique : {$this->thirdParty->name}")
            ->body("Le statut juridique est passé à : {$newLabel}.")
            ->actions([
                Action::make('show')
                    ->label('Voir le tiers')
                    ->icon(Phosphor::Eye)
                    ->url(route('filament.tiers.resources.third-parties.edit', ['record' => $this->thirdParty])),
            ])
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [
            'third_party_id' => $this->thirdParty->id,
            'title' => "Alerte statut juridique : {$this->thirdParty->name}",
            'message' => "Le statut juridique est passé à : " . ($this->newStatus?->getLabel() ?? 'Inconnu'),
            'previous_status' => $this->previousStatus?->value,
            'new_status' => $this->newStatus?->value,
            'icon' => Phosphor::WarningCircle,
            'color' => 'danger',
        ];
    }
}
