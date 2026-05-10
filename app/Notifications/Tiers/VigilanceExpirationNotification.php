<?php

namespace App\Notifications\Tiers;

use App\Models\Tiers\ThirdParty;
use Filament\Actions\Action;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class VigilanceExpirationNotification extends Notification
{
    public function __construct(
        protected ThirdParty $thirdParty,
        protected array $issues
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $issuesList = implode(', ', $this->issues);

        return (new MailMessage)
            ->subject("Alerte Vigilance : {$this->thirdParty->name}")
            ->greeting('Bonjour,')
            ->line("Des documents critiques pour le tiers {$this->thirdParty->name} arrivent à expiration ou sont manquants.")
            ->line("Détails : {$issuesList}")
            ->action('Gérer le tiers', url("/tiers/{$this->thirdParty->id}/edit"))
            ->line('Veuillez régulariser la situation pour éviter tout blocage de paiement.');
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->warning()
            ->title("Alerte Vigilance : {$this->thirdParty->name}")
            ->body("Des documents critiques pour le tiers {$this->thirdParty->name} arrivent à expiration ou sont manquants.")
            ->actions([
                Action::make('show')
                    ->label('Gérer le tiers')
                    ->icon(Phosphor::Eye)
                    ->url(url("/tiers/{$this->thirdParty->id}/edit")),
            ])
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [
            'third_party_id' => $this->thirdParty->id,
            'title' => "Conformité : {$this->thirdParty->name}",
            'message' => 'Problèmes détectés : '.implode(', ', $this->issues),
            'icon' => Phosphor::WarningCircle,
            'color' => 'danger',
        ];
    }
}
