<?php

namespace App\Notifications\Customer;

use App\Models\Commerce\CustomerInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RelanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public array $levels = [
        1 => [
            'subject' => 'Rappel : Paiement en attente',
            'greeting' => 'Rappel de paiement',
            'color' => 'primary',
        ],
        2 => [
            'subject' => 'Mise en demeure de paiement',
            'greeting' => 'Mise en demeure',
            'color' => 'warning',
        ],
        3 => [
            'subject' => 'Dernière relance avant contentieux',
            'greeting' => 'Dernière relance',
            'color' => 'danger',
        ],
    ];

    public function __construct(
        public CustomerInvoice $invoice,
        public int $level,
        public int $daysLate
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $config = $this->levels[$this->level];

        $mail = (new MailMessage)
            ->subject($config['subject']." — Facture n°{$this->invoice->reference}")
            ->greeting($config['greeting']);

        match ($this->level) {
            1 => $this->addLevel1Content($mail),
            2 => $this->addLevel2Content($mail),
            3 => $this->addLevel3Content($mail),
        };

        return $mail
            ->line("**Facture** : {$this->invoice->reference}")
            ->line('**Montant dû** : '.number_format($this->invoice->total_ttc, 2, ',', ' ').' € TTC')
            ->line("**Échéance** : {$this->invoice->due_date->format('d/m/Y')}")
            ->line("**Jours de retard** : {$this->daysLate}")
            ->action('Consulter la facture', url("/customer/customer-invoices/{$this->invoice->id}"))
            ->line('Merci de régulariser votre situation.');
    }

    public function toDatabase($notifiable): array
    {
        $config = $this->levels[$this->level];

        return \Filament\Notifications\Notification::make()
            ->title($config['subject'])
            ->body("Facture {$this->invoice->reference} — {$this->daysLate} jour(s) de retard")
            ->getDatabaseMessage();
    }

    protected function addLevel1Content(MailMessage $mail): MailMessage
    {
        return $mail
            ->line("Nous vous rappelons que la facture n°{$this->invoice->reference} est en attente de paiement depuis {$this->daysLate} jours.")
            ->line('Veuillez procéder au paiement à votre convenance.');
    }

    protected function addLevel2Content(MailMessage $mail): MailMessage
    {
        return $mail
            ->error()
            ->line("Malgré notre relance précédente, le paiement de la facture n°{$this->invoice->reference} n'a pas été reçu.")
            ->line('**Nous vous mettons en demeure de payer dans un délai de 8 jours**.')
            ->line('Veuillez régulariser cette situation.');
    }

    protected function addLevel3Content(MailMessage $mail): MailMessage
    {
        return $mail
            ->error()
            ->line("Ceci est notre **dernière relance** concernant la facture n°{$this->invoice->reference}.")
            ->line('À défaut de paiement dans **3 jours**, nous engagerons une procédure judiciaire.')
            ->line('**Agissez rapidement pour éviter des poursuites.**');
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
