<?php

namespace App\Notifications\RH;

use App\Filament\RH\Resources\Employees\EmployeeResource;
use App\Models\RH\Contract;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ContractTerminatedNotification extends Notification
{
    public function __construct(public Contract $contract) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $type = $this->contract->termination_type->getLabel();

        return (new MailMessage)
            ->subject("Rupture de contrat – {$type}")
            ->greeting("Bonjour {$notifiable->full_name},")
            ->line("Votre contrat ({$this->contract->job_title}) a été rompu par {$type}.")
            ->line('Date de fin effective : '.$this->contract->notice_end_date->format('d/m/Y'))
            ->line('Motif : '.($this->contract->termination_reason ?: 'Non précisé'))
            ->line('Vous recevrez les documents officiels dans les plus brefs délais.');
    }

    public function toDatabase($notifiable): array
    {
        return [];
    }

    public function toArray($notifiable): array
    {
        $type = $this->contract->termination_type->getLabel();

        return [
            'title' => 'Contrat rompu – '.$type,
            'body' => 'Votre contrat a été terminé le '.$this->contract->notice_end_date->format('d/m/Y').'.',
            'icon' => Phosphor::FileX,
            'color' => 'danger',
            'url' => EmployeeResource::getUrl('edit', ['record' => $this->contract->employee_id]),
        ];
    }

    public function toFilament($notifiable): ?FilamentNotification
    {
        $type = $this->contract->termination_type->getLabel();

        return FilamentNotification::make()
            ->title("Contrat rompu – {$type}")
            ->body("Contrat de {$this->contract->job_title} terminé le {$this->contract->notice_end_date->format('d/m/Y')}.")
            ->danger()
            ->icon(Phosphor::FileX)
            ->actions([
                Action::make('view')
                    ->label('Voir le contrat')
                    ->url(EmployeeResource::getUrl('edit', [
                        'record' => $this->contract->employee_id,
                    ])),
            ]);
    }
}
