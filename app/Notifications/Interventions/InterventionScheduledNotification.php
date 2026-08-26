<?php

namespace App\Notifications\Interventions;

use App\Models\Interventions\Intervention;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class InterventionScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Intervention $intervention
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'intervention_id' => $this->intervention->id,
            'reference' => $this->intervention->reference,
            'message' => "Vous avez été assigné à l'intervention {$this->intervention->reference}.",
        ];
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('Nouvelle Intervention')
            ->icon('/images/icon.png')
            ->body("Vous avez été assigné à l'intervention {$this->intervention->reference} prévue le ".($this->intervention->scheduled_at ? $this->intervention->scheduled_at->format('d/m/Y') : 'À définir'))
            ->action('Voir', '/espace-salarie/interventions');
    }
}
