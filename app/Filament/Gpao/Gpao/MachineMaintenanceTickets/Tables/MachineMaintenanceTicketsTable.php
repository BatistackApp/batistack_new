<?php

namespace App\Filament\Gpao\Gpao\MachineMaintenanceTickets\Tables;

use App\Enums\Gpao\MachineMaintenanceTicketStatus;
use App\Enums\Gpao\MachineMaintenanceTicketType;
use App\Filament\Gpao\Gpao\MachineMaintenanceTickets\Actions\ResolvableTicketActions;
use App\Models\Gpao\MachineMaintenanceTicket;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MachineMaintenanceTicketsTable
{
    use ResolvableTicketActions;

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
                    static::getStartAction(),
                    static::getResolveAction(),
                    static::getCancelAction(),
                ]),
            ]);
    }
}
