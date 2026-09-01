<?php

namespace App\Filament\Gpao\Gpao\Machines\RelationManagers;

use App\Enums\Gpao\MachineMaintenanceTicketStatus;
use App\Enums\Gpao\MachineMaintenanceTicketType;
use App\Models\Gpao\MachineMaintenanceTicket;
use App\Services\Gpao\MachineMaintenanceTicketService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaintenanceTicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenanceTickets';

    protected static ?string $title = 'Tickets de Maintenance';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('type')
                            ->label('Type')
                            ->options(MachineMaintenanceTicketType::class)
                            ->required()
                            ->native(false),
                        Select::make('status')
                            ->label('Statut')
                            ->options(MachineMaintenanceTicketStatus::class)
                            ->default(MachineMaintenanceTicketStatus::OPEN)
                            ->required()
                            ->native(false),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->required(),
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
                            ->rows(2)
                            ->nullable(),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
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
                    ->limit(40)
                    ->tooltip(fn (MachineMaintenanceTicket $record) => $record->description),
                TextColumn::make('cost_ht')
                    ->label('Coût HT')
                    ->money('EUR')
                    ->placeholder('—'),
                TextColumn::make('provider_name')
                    ->label('Prestataire')
                    ->placeholder('—'),
                TextColumn::make('resolved_at')
                    ->label('Résolu le')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date('d/m/Y'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(MachineMaintenanceTicketStatus::class),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(MachineMaintenanceTicketType::class),
            ])
            ->headerActions([
                CreateAction::make()->label('Créer un ticket'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('start')
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
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
