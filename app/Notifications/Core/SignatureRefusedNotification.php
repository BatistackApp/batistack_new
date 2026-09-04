<?php

namespace App\Notifications\Core;

use App\Models\Core\Signature;
use App\Models\Core\SignatureSigner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SignatureRefusedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Signature $signature,
        public SignatureSigner $signer
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
            ->subject('Signature refusée — '.$modelLabel)
            ->line('Un signataire a refusé de signer le document.')
            ->line('Signataire : '.$this->signer->name.' ('.$this->signer->email.')')
            ->line('Rôle : '.$this->signer->role)
            ->line('Type : '.$modelLabel.' #'.$model->id)
            ->line('Le workflow de signature a été arrêté.')
            ->action('Voir le document', route('filament.signatures.resources.signatures.view', $this->signature));
    }
}
