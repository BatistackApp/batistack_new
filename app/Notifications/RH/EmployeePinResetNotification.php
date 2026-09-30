<?php

namespace App\Notifications\RH;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class EmployeePinResetNotification extends Notification
{
    public function __construct(public string $pin) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Batistack — Votre code PIN provisoire')
            ->greeting("Bonjour {$notifiable->full_name},")
            ->line('Un code PIN provisoire a été généré pour votre compte :')
            ->line($this->pin)
            ->line('Ce code est nécessaire pour valider les états des lieux de votre véhicule.')
            ->line('ATTENTION : ce code est provisoire. Connectez-vous à votre espace puis changez-le depuis Mon Profil → Code PIN.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Code PIN provisoire',
            'body' => 'Un code PIN provisoire vous a été envoyé par email. Pensez à le modifier depuis votre profil (Mon Profil → Code PIN).',
            'icon' => Phosphor::Key,
            'color' => 'warning',
        ];
    }

    public function toFilament($notifiable): ?FilamentNotification
    {
        return FilamentNotification::make()
            ->title('Code PIN provisoire')
            ->body('Un code PIN provisoire vous a été envoyé par email. Modifiez-le depuis Mon Profil.')
            ->warning()
            ->icon(Phosphor::Key)
            ->actions([
                Action::make('open_profile')
                    ->label('Mon Profil')
                    ->url('/salarie/mon-profil'),
            ]);
    }
}
