<?php

namespace App\Filament\Gpao\Gpao\MachineMaintenanceTickets\Actions;

use App\Enums\Gpao\MachineMaintenanceTicketStatus;
use App\Models\Gpao\MachineMaintenanceTicket;
use App\Services\Gpao\MachineMaintenanceTicketService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

trait ResolvableTicketActions
{
    public static function getStartAction(): Action
    {
        return Action::make('start')
            ->label('Prendre en charge')
            ->icon('heroicon-o-play')
            ->color('primary')
            ->visible(fn (MachineMaintenanceTicket $record) => $record->status === MachineMaintenanceTicketStatus::OPEN)
            ->action(function (MachineMaintenanceTicket $record) {
                app(MachineMaintenanceTicketService::class)->start($record);

                Notification::make()
                    ->title('Ticket pris en charge')
                    ->success()
                    ->send();
            });
    }

    public static function getResolveAction(): Action
    {
        return Action::make('resolve')
            ->label('Résoudre')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (MachineMaintenanceTicket $record) => in_array($record->status, [
                MachineMaintenanceTicketStatus::OPEN,
                MachineMaintenanceTicketStatus::IN_PROGRESS,
            ], true))
            ->schema([
                TextInput::make('cost_ht')
                    ->label('Coût HT')
                    ->numeric()
                    ->prefix('€')
                    ->minValue(0)
                    ->nullable(),
                TextInput::make('provider_name')
                    ->label('Prestataire')
                    ->nullable(),
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->nullable(),
            ])
            ->action(function (MachineMaintenanceTicket $record, array $data) {
                app(MachineMaintenanceTicketService::class)->resolve(
                    $record,
                    $data['cost_ht'] ?? null,
                    $data['provider_name'] ?? null,
                    $data['notes'] ?? null,
                );

                Notification::make()
                    ->title('Ticket résolu')
                    ->success()
                    ->send();
            });
    }

    public static function getCancelAction(): Action
    {
        return Action::make('cancel')
            ->label('Annuler')
            ->icon('heroicon-o-x-circle')
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (MachineMaintenanceTicket $record) => in_array($record->status, [
                MachineMaintenanceTicketStatus::OPEN,
                MachineMaintenanceTicketStatus::IN_PROGRESS,
            ], true))
            ->action(function (MachineMaintenanceTicket $record) {
                app(MachineMaintenanceTicketService::class)->cancel($record);

                Notification::make()
                    ->title('Ticket annulé')
                    ->warning()
                    ->send();
            });
    }
}
