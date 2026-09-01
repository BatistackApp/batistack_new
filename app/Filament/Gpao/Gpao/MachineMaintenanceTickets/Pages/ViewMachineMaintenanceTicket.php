<?php

namespace App\Filament\Gpao\Gpao\MachineMaintenanceTickets\Pages;

use App\Enums\Gpao\MachineMaintenanceTicketStatus;
use App\Filament\Gpao\Gpao\MachineMaintenanceTickets\MachineMaintenanceTicketResource;
use App\Services\Gpao\MachineMaintenanceTicketService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewMachineMaintenanceTicket extends ViewRecord
{
    protected static string $resource = MachineMaintenanceTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('start')
                ->label('Prendre en charge')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->visible(fn () => $this->getRecord()->status === MachineMaintenanceTicketStatus::OPEN)
                ->action(function () {
                    app(MachineMaintenanceTicketService::class)->start($this->getRecord());

                    Notification::make()
                        ->title('Ticket pris en charge')
                        ->success()
                        ->send();
                }),
            Action::make('resolve')
                ->label('Résoudre')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => in_array($this->getRecord()->status, [
                    MachineMaintenanceTicketStatus::OPEN,
                    MachineMaintenanceTicketStatus::IN_PROGRESS,
                ], true))
                ->schema([
                    TextInput::make('cost_ht')
                        ->label('Coût HT')
                        ->numeric()
                        ->prefix('€')
                        ->nullable(),
                    TextInput::make('provider_name')
                        ->label('Prestataire')
                        ->nullable(),
                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(3)
                        ->nullable(),
                ])
                ->action(function (array $data) {
                    app(MachineMaintenanceTicketService::class)->resolve(
                        $this->getRecord(),
                        $data['cost_ht'] ?? null,
                        $data['provider_name'] ?? null,
                        $data['notes'] ?? null,
                    );

                    Notification::make()
                        ->title('Ticket résolu')
                        ->success()
                        ->send();
                }),
            Action::make('cancel')
                ->label('Annuler le ticket')
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn () => in_array($this->getRecord()->status, [
                    MachineMaintenanceTicketStatus::OPEN,
                    MachineMaintenanceTicketStatus::IN_PROGRESS,
                ], true))
                ->action(function () {
                    app(MachineMaintenanceTicketService::class)->cancel($this->getRecord());

                    Notification::make()
                        ->title('Ticket annulé')
                        ->warning()
                        ->send();
                }),
        ];
    }
}
