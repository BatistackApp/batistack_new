<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenanceTickets\Schemas;

use App\Enums\Immobilisation\AssetMaintenanceTicketStatus;
use App\Enums\Immobilisation\TicketSeverity;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetMaintenanceTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticket')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('reference')
                            ->label('Référence')
                            ->disabled(),
                        Select::make('status')
                            ->label('Statut')
                            ->options(AssetMaintenanceTicketStatus::class)
                            ->required(),
                        Select::make('severity')
                            ->label('Gravité')
                            ->options(TicketSeverity::class)
                            ->required(),
                        Select::make('chantier_id')
                            ->label('Chantier')
                            ->relationship('chantier', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        TextInput::make('cost_ht')
                            ->label('Coût HT')
                            ->numeric()
                            ->prefix('€')
                            ->nullable(),
                        TextInput::make('provider_name')
                            ->label('Prestataire')
                            ->nullable(),
                        Textarea::make('description')
                            ->label('Description du sinistre')
                            ->columnSpanFull(),
                        SpatieMediaLibraryFileUpload::make('photos')
                            ->label('Photos')
                            ->collection('photos')
                            ->image()
                            ->multiple()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
