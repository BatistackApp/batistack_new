<?php

namespace App\Notifications\Core;

use App\Models\Core\Signature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SignatureCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Signature $signature
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $model = $this->signature->signable;
        $modelLabel = class_basename($model);

        return (new MailMessage)
            ->subject('Signature complétée — '.$modelLabel)
            ->line('Tous les signataires ont signé le document.')
            ->line('Type : '.$modelLabel.' #'.$model->id)
            ->line('Date : '.$this->signature->signed_at->format('d/m/Y H:i'))
            ->action('Voir le document', route('filament.core.resources.signatures.view', $this->signature));
    }
}
