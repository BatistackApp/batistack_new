<?php

namespace App\Notifications\RH;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class WelcomeEmployeeNotification extends Notification
{
    public function __construct() {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        // Génération d'un token de réinitialisation de mot de passe
        $token = Password::createToken($notifiable);
        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Bienvenue chez Batistack ! Activation de votre compte')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Votre profil collaborateur a été créé sur Batistack, l'ERP de votre entreprise.")
            ->line('Pour commencer à utiliser vos outils (pointage, chantiers, documents RH), vous devez définir votre mot de passe.')
            ->action('Définir mon mot de passe', $url)
            ->line('Ce lien expirera dans 24 heures.')
            ->line("Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet email.");
    }

    public function toDatabase($notifiable): array
    {
        return [];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Bienvenue sur Batistack !',
            'body' => 'Votre compte est actif. N\'oubliez pas de compléter votre profil.',
            'icon' => Phosphor::Confetti,
            'color' => 'success',
        ];
    }
}
