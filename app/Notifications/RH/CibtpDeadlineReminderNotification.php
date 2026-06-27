<?php

namespace App\Notifications\RH;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CibtpDeadlineReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $urgentCount
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("URGENT : {$this->urgentCount} déclaration(s) CIBTP en attente")
            ->error()
            ->line("Attention : {$this->urgentCount} déclaration(s) d'intempéries sont au statut brouillon depuis plus de 15 jours.")
            ->line("Le délai légal strict est de 30 jours pour transmettre ces déclarations à la CIBTP, sous peine de refus d'indemnisation.")
            ->action('Gérer les déclarations', url('/admin/cibtp-declarations'))
            ->line("Merci de les traiter dans les plus brefs délais.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Déclarations CIBTP Urgentes',
            'message' => "{$this->urgentCount} déclaration(s) en attente depuis >15 jours.",
            'count' => $this->urgentCount,
        ];
    }
}
