<?php

namespace App\Notifications\Chantiers;

use App\Models\Chantiers\Chantier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ChantierBudgetExceededNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Chantier $chantier,
        public float $marginAmount
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // On pourrait ajouter 'mail' plus tard
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'chantier_id' => $this->chantier->id,
            'chantier_name' => $this->chantier->name,
            'margin_amount' => $this->marginAmount,
            'message' => "Le chantier {$this->chantier->name} est passé en marge négative (".number_format($this->marginAmount, 2, ',', ' ').' €).',
        ];
    }
}
