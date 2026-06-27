<?php

namespace App\Notifications\RH;

use App\Models\RH\CibtpDeclaration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CibtpDeclarationNeededNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CibtpDeclaration $declaration)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Action requise: Brouillon CIBTP généré')
            ->line("Un brouillon de déclaration intempéries CIBTP a été généré pour le chantier {$this->declaration->chantier->name}.")
            ->line("Heures perdues estimées : {$this->declaration->total_lost_hours}h.")
            ->action('Valider la déclaration', url('/rh/cibtp-declarations/' . $this->declaration->id))
            ->line('Merci de vérifier et soumettre la déclaration sur le portail Net-Entreprises.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'cibtp_declaration_id' => $this->declaration->id,
            'chantier_id' => $this->declaration->chantier_id,
            'message' => "Brouillon CIBTP à valider pour {$this->declaration->chantier->name}",
        ];
    }
}
