<?php

namespace App\Filament\Gpao\Gpao\Machines\RelationManagers;

use App\Enums\Gpao\MachineMaintenanceTicketStatus;
use App\Enums\Gpao\MachineMaintenanceTicketType;
use App\Filament\Gpao\Gpao\MachineMaintenanceTickets\Actions\ResolvableTicketActions;
use App\Models\Gpao\MachineMaintenanceTicket;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaintenanceTicketsRelationManager extends RelationManager
{
    use ResolvableTicketActions;

    protected static string $relationship = 'maintenanceTickets';

    protected static ?string $title = 'Tickets de Maintenance';

    protected static ?string $recordTitleAttribute = 'description';

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
                            ->minValue(0)
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
                static::getStartAction(),
                static::getResolveAction(),
                static::getCancelAction(),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
