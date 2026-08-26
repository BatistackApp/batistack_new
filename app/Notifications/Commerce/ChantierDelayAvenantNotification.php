<?php

namespace App\Notifications\Commerce;

use App\Models\Chantiers\Chantier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChantierDelayAvenantNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Chantier $chantier,
        public int $lostDays
    ) {}

    public function via(object $notifiable): array
    {
        // We use 'mail' and 'database' for internal users.
        // For external routes (like the client), it might not have 'database', but Laravel handles this gracefully.
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Avenant de délai recommandé : {$this->chantier->name}")
            ->line("Le chantier {$this->chantier->name} vient de subir un décalage de planning suite à des intempéries.")
            ->line("Le délai supplémentaire estimé est de : {$this->lostDays} jour(s) calendaire(s).")
            ->line('La date de fin prévisionnelle a été repoussée au : '.($this->chantier->end_date_preview ? $this->chantier->end_date_preview->format('d/m/Y') : 'Non définie'))
            ->line('Il est fortement recommandé de proposer un avenant de délai au client pour éviter des pénalités de retard.')
            ->action('Voir le Chantier', url("/admin/chantiers/{$this->chantier->id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'chantier_id' => $this->chantier->id,
            'title' => "Décalage de planning ({$this->lostDays} jours)",
            'message' => "Le chantier {$this->chantier->name} nécessite potentiellement un avenant de délai suite aux intempéries.",
        ];
    }
}
