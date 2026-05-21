<?php

namespace App\Notifications\Commerce;

use App\Models\Commerce\CustomerInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public array $levels = [
        1 => [
            'subject' => '⏰ Rappel : Paiement en attente',
            'greeting' => 'Rappel de paiement',
            'color' => 'primary',
        ],
        2 => [
            'subject' => '🚨 Mise en demeure de paiement',
            'greeting' => 'Mise en demeure',
            'color' => 'warning',
        ],
        3 => [
            'subject' => '⚖️ Dernière relance avant contentieux',
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
            ->subject($config['subject']." - Facture n°{$this->invoice->reference}")
            ->greeting($config['greeting']);

        // Contenu selon le niveau
        match ($this->level) {
            1 => $this->addLevel1Content($mail),
            2 => $this->addLevel2Content($mail),
            3 => $this->addLevel3Content($mail),
        };

        return $mail
            ->line("**Numéro de facture** : {$this->invoice->reference}")
            ->line('**Montant dû** : '.number_format($this->invoice->total_ttc, 2, ',', ' ').' € TTC')
            ->line("**Date d'échéance** : {$this->invoice->due_date->format('d/m/Y')}")
            ->line("**Jours de retard** : {$this->daysLate}")
            ->action('Payer maintenant', url("/commerce/invoices/{$this->invoice->id}/pay"))
            ->line('Merci de régulariser votre situation au plus tôt.');
    }

    public function toDatabase($notifiable): array
    {
        $config = $this->levels[$this->level];
        $colorMethod = match ($this->level) {
            1 => 'primary',
            2 => 'warning',
            3 => 'danger',
        };

        return \Filament\Notifications\Notification::make()
            ->$colorMethod()
            ->title($config['subject'])
            ->body("Facture {$this->invoice->reference} - {$this->daysLate} jours de retard")
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
            ->line("Malgré notre relance précédente, nous ne avons pas reçu le paiement de la facture n°{$this->invoice->reference}.")
            ->line('**Nous mettons en demeure le paiement dans un délai de 8 jours**, passé lequel nous engagerons une procédure judiciaire.')
            ->line("Veuillez régulariser cette situation d'urgence.");
    }

    protected function addLevel3Content(MailMessage $mail): MailMessage
    {
        return $mail
            ->error()
            ->line("Ceci est notre **dernière relance** concernant la facture n°{$this->invoice->reference}.")
            ->line("À défaut de paiement complet du montant dû dans un délai de **3 jours**, nous procèderons à l'engagement d'une action en justice.")
            ->line('Aucune démarche supplémentaire ne sera entreprise avant cette date.')
            ->line('**Agissez immédiatement pour éviter des poursuites judiciaires.**');
    }
}
