<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenanceTickets\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AssetMaintenanceTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Actif concerné')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('asset.name')
                            ->label('Actif')
                            ->state(fn ($record) => $record->asset?->name ?? '—'),
                        TextEntry::make('asset_type')
                            ->label('Type d\'actif')
                            ->state(fn ($record) => $record->asset ? class_basename($record->asset_type) : '—'),
                        TextEntry::make('asset.serial_number')
                            ->label('N° série'),
                        TextEntry::make('asset.status')
                            ->label('Statut de l\'actif')
                            ->badge()
                            ->state(fn ($record) => $record->asset?->status?->getLabel() ?? '—'),
                    ]),
                Section::make('Ticket')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('reference')
                            ->label('Référence')
                            ->copyable(),
                        TextEntry::make('status')
                            ->label('Statut')
                            ->badge(),
                        TextEntry::make('severity')
                            ->label('Gravité')
                            ->badge(),
                        TextEntry::make('reportedBy.full_name')
                            ->label('Déclaré par'),
                        TextEntry::make('chantier.name')
                            ->label('Chantier'),
                        TextEntry::make('created_at')
                            ->label('Créé le')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('resolved_at')
                            ->label('Résolu le')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('cost_ht')
                            ->label('Coût HT')
                            ->money('EUR'),
                        TextEntry::make('provider_name')
                            ->label('Prestataire'),
                        TextEntry::make('description')
                            ->label('Description du sinistre')
                            ->columnSpanFull(),
                    ]),
                Section::make('Photos')
                    ->schema([
                        ImageEntry::make('photos')
                            ->collection('photos')
                            ->label('Photos')
                            ->height(120),
                    ]),
            ]);
    }
}
