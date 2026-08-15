<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenanceTickets\Pages;

use App\Enums\Immobilisation\AssetMaintenanceTicketStatus;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenanceTickets\AssetMaintenanceTicketResource;
use App\Services\Immobilisation\AssetMaintenanceTicketService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewAssetMaintenanceTicket extends ViewRecord
{
    protected static string $resource = AssetMaintenanceTicketResource::class;

    protected function getHeaderActions(): array
    {
        $service = app(AssetMaintenanceTicketService::class);

        return [
            Action::make('start')
                ->label('Prendre en charge')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->visible(fn () => $this->getRecord()->status === AssetMaintenanceTicketStatus::OPEN)
                ->action(function () use ($service) {
                    $service->start($this->getRecord());

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
                    AssetMaintenanceTicketStatus::OPEN,
                    AssetMaintenanceTicketStatus::IN_PROGRESS,
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
                ])
                ->action(function (array $data) use ($service) {
                    $service->resolve($this->getRecord(), $data['cost_ht'] ?? null, $data['provider_name'] ?? null);

                    Notification::make()
                        ->title('Ticket résolu')
                        ->body('Enregistrement de maintenance créé.')
                        ->success()
                        ->send();
                }),
            Action::make('cancel')
                ->label('Annuler le ticket')
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn () => in_array($this->getRecord()->status, [
                    AssetMaintenanceTicketStatus::OPEN,
                    AssetMaintenanceTicketStatus::IN_PROGRESS,
                ], true))
                ->action(function () use ($service) {
                    $service->cancel($this->getRecord());

                    Notification::make()
                        ->title('Ticket annulé')
                        ->warning()
                        ->send();
                }),
        ];
    }
}
