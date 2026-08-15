<?php

namespace App\Filament\Interventions\Schemas;

use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MaintenanceContractInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contrat')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('reference')
                            ->label('Référence')
                            ->copyable()
                            ->weight('bold'),
                        TextEntry::make('name')
                            ->label('Nom du contrat'),
                        TextEntry::make('status')
                            ->label('Statut')
                            ->badge(),
                        TextEntry::make('frequency')
                            ->label('Fréquence')
                            ->badge(),
                        TextEntry::make('thirdParty.name')
                            ->label('Client'),
                        TextEntry::make('clientEquipment.name')
                            ->label('Équipement'),
                        TextEntry::make('chantier.name')
                            ->label('Chantier')
                            ->placeholder('—'),
                        TextEntry::make('start_date')
                            ->label('Début')
                            ->date('d/m/Y'),
                        TextEntry::make('end_date')
                            ->label('Fin')
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('next_due_date')
                            ->label('Prochaine échéance')
                            ->date('d/m/Y')
                            ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'gray'),
                        TextEntry::make('last_generated_at')
                            ->label('Dernière génération')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Jamais'),
                        TextEntry::make('flat_rate_price')
                            ->label('Prix forfaitaire HT')
                            ->money('EUR'),
                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
