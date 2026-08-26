<?php

namespace App\Notifications\Paie;

use App\Models\Paie\DsnSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DsnExportedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DsnSubmission $submission) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("DSN exportée — {$this->submission->period}")
            ->line("L'export DSN pour la période {$this->submission->period} a été généré avec succès.")
            ->line("{$this->submission->payslips_count} bulletins inclus — Montant brut total : ".number_format($this->submission->total_gross, 2, ',', ' ').' €')
            ->action('Voir la soumission', url('/paie/dsn-submissions/'.$this->submission->id))
            ->line('Le fichier CSV est prêt à être transmis à votre expert-comptable.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'dsn_submission_id' => $this->submission->id,
            'period' => $this->submission->period,
            'payslips_count' => $this->submission->payslips_count,
            'message' => "Export DSN {$this->submission->period} prêt",
        ];
    }
}
