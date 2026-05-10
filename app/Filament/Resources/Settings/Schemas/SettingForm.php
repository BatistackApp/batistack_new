<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Settings')
                    ->tabs([
                        Tabs\Tab::make('Configuration')
                            ->icon(Phosphor::Sliders)
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('key')
                                        ->label('Identifiant système (Clé)')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->required(),
                                    TextInput::make('group')
                                        ->label('Groupe de paramètres')
                                        ->disabled(),
                                ]),
                                Textarea::make('value')
                                    ->label('Valeur enregistrée')
                                    ->rows(5)
                                    ->required(),
                            ]),
                        Tabs\Tab::make('Méta-données')
                            ->icon(Phosphor::Info)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Date de création')
                                    ->state(fn ($record): string => $record?->created_at->diffForHumans() ?? '-'),
                                TextEntry::make('updated_at')
                                    ->label('Dernière modification')
                                    ->state(fn ($record): string => $record?->updated_at->diffForHumans() ?? '-'),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
