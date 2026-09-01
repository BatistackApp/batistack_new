<?php

namespace App\Filament\Gpao\Gpao\MachineMaintenanceTickets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MachineMaintenanceTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informations Machine')
                    ->schema([
                        TextEntry::make('machine.name')
                            ->label('Machine')
                            ->weight('bold'),
                        TextEntry::make('machine.reference')
                            ->label('Référence'),
                        TextEntry::make('machine.status')
                            ->label('Statut machine')
                            ->badge(),
                    ])->columns(3),

                Section::make('Détails du Ticket')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Statut')
                            ->badge(),
                        TextEntry::make('type')
                            ->label('Type')
                            ->badge(),
                        TextEntry::make('cost_ht')
                            ->label('Coût HT')
                            ->money('EUR')
                            ->placeholder('—'),
                        TextEntry::make('provider_name')
                            ->label('Prestataire')
                            ->placeholder('—'),
                        TextEntry::make('reportedBy.name')
                            ->label('Déclaré par')
                            ->placeholder('Automatique (système)'),
                        TextEntry::make('resolved_at')
                            ->label('Résolu le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                        TextEntry::make('notes')
                            ->label('Notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label('Créé le')
                            ->dateTime('d/m/Y H:i'),
                    ])->columns(3),
            ]);
    }
}
