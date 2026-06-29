<?php

namespace App\Notifications\Tiers;

use App\Models\Tiers\ThirdParty;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubcontractorVigilanceReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ThirdParty $thirdParty,
        protected array $issues
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $issuesList = implode(', ', $this->issues);

        return (new MailMessage)
            ->subject("URGENT : Mise à jour de vos documents légaux (Vigilance)")
            ->greeting("Bonjour,")
            ->line("En tant que partenaire de notre entreprise, vous avez l'obligation de nous fournir vos documents légaux à jour.")
            ->line("Notre système a détecté que les documents suivants sont manquants ou arrivés à expiration :")
            ->error()
            ->line($issuesList)
            ->line("Afin de maintenir notre collaboration et de ne pas bloquer vos futurs règlements, merci de nous transmettre les pièces mises à jour dans les plus brefs délais.")
            ->line("Cordialement, L'équipe administrative.");
    }
}
