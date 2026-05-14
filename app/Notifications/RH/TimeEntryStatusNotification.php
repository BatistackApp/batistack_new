<?php

namespace App\Notifications\RH;

use App\Enums\RH\TimeEntryStatus;
use App\Models\RH\TimeEntry;
use Illuminate\Notifications\Notification;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class TimeEntryStatusNotification extends Notification
{
    public function __construct(protected TimeEntry $timeEntry) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [];
    }

    public function toArray($notifiable): array
    {
        return match ($this->timeEntry->status) {
            TimeEntryStatus::SUBMITTED => [
                'title' => 'Nouveau pointage à valider',
                'body' => "{$this->timeEntry->employee->full_name} a soumis {$this->timeEntry->hours}h pour le {$this->timeEntry->date->format('d/m/Y')}.",
                'icon' => Phosphor::PaperPlaneTilt,
                'color' => 'warning',
            ],
            TimeEntryStatus::APPROVED => [
                'title' => 'Pointage approuvé',
                'body' => "Vos heures du {$this->timeEntry->date->format('d/m/Y')} ont été validées.",
                'icon' => Phosphor::CheckCircle,
                'color' => 'success',
            ],
            TimeEntryStatus::DRAFT => [
                'title' => 'Pointage refusé / à corriger',
                'body' => 'Motif : '.($this->timeEntry->refusal_reason ?? 'Non spécifié'),
                'icon' => Phosphor::XCircle,
                'color' => 'danger',
            ],
            default => [],
        };
    }
}
