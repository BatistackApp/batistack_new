<?php

namespace App\Notifications\Tiers;

use App\Models\Core\Company;
use App\Models\Tiers\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeCustomerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Contact  $contact)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $token = \Password::createToken($notifiable);
        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $companyName = Company::first()?->legal_name ?? config('app.name', 'Batistack');

        return (new MailMessage)
            ->subject('Bienvenue chez '.$companyName)
            ->greeting('Bonjour '.$notifiable->name.' !')
            ->line('Nous sommes ravis de vous compter parmi nos nouveaux clients.')
            ->line('Votre compte a été créé avec succès. Pour commencer à utiliser nos services, vous devez définir votre mot de passe en cliquant sur le bouton ci-dessous :')
            ->action('Définir mon mot de passe', $url)
            ->line('Ce lien de réinitialisation de mot de passe expirera dans 60 minutes.')
            ->line('Si vous n\'avez pas demandé la création de ce compte, aucune action supplémentaire n\'est requise.')
            ->line('Merci de votre confiance !');

    }
}
