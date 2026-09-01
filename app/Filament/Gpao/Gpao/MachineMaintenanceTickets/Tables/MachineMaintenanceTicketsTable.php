<?php

namespace App\Filament\Gpao\Gpao\MachineMaintenanceTickets\Tables;

use App\Enums\Gpao\MachineMaintenanceTicketStatus;
use App\Enums\Gpao\MachineMaintenanceTicketType;
use App\Models\Gpao\MachineMaintenanceTicket;
use App\Services\Gpao\MachineMaintenanceTicketService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MachineMaintenanceTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('machine.name')
                    ->label('Machine')
                    ->searchable()
                    ->description(fn (MachineMaintenanceTicket $record) => $record->machine?->reference ?? ''),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(fn (MachineMaintenanceTicket $record) => $record->description),
                TextColumn::make('cost_ht')
                    ->label('Coût HT')
                    ->money('EUR')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('provider_name')
                    ->label('Prestataire')
                    ->placeholder('—'),
                TextColumn::make('reportedBy.name')
                    ->label('Déclaré par')
                    ->placeholder('Automatique'),
                TextColumn::make('resolved_at')
                    ->label('Résolu le')
                    ->date('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(MachineMaintenanceTicketStatus::class),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(MachineMaintenanceTicketType::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    Action::make('start')
                        ->label('Prendre en charge')
                        ->icon('heroicon-o-play')
                        ->color('primary')
                        ->visible(fn (MachineMaintenanceTicket $record) => $record->status === MachineMaintenanceTicketStatus::OPEN)
                        ->action(function (MachineMaintenanceTicket $record) {
                            app(MachineMaintenanceTicketService::class)->start($record);

                            Notification::make()
                                ->title('Ticket pris en charge')
                                ->body("Machine : {$record->machine?->name}")
                                ->success()
                                ->send();
                        }),
                    Action::make('resolve')
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
                                ->body("Machine : {$record->machine?->name}")
                                ->success()
                                ->send();
                        }),
                    Action::make('cancel')
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
                        }),
                ]),
            ]);
    }
}
