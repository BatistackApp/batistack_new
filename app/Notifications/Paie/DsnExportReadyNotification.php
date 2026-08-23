<?php

namespace App\Notifications\Paie;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DsnExportReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $period, public int $payslipsCount, public float $totalGross)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("DSN prête à exporter — {$this->period}")
            ->line("Tous les bulletins de la période {$this->period} sont validés ({$this->payslipsCount} bulletins).")
            ->line("Montant brut total : " . number_format($this->totalGross, 2, ',', ' ') . " €")
            ->action('Exporter la DSN', url('/admin/payslips'))
            ->line('Vous pouvez maintenant lancer l\'export CSV pour votre expert-comptable.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'period' => $this->period,
            'payslips_count' => $this->payslipsCount,
            'total_gross' => $this->totalGross,
            'message' => "DSN {$this->period} prête à exporter ({$this->payslipsCount} bulletins)",
        ];
    }
}
