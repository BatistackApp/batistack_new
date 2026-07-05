<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                                        ->datalist(['Général', 'Facturation', 'Interface', 'API']),
                                ]),

                                Select::make('type')
                                    ->label('Format de la donnée')
                                    ->options([
                                        'string' => 'Texte',
                                        'integer' => 'Nombre entier',
                                        'boolean' => 'Booléen (Oui / Non)',
                                        'color' => 'Couleur',
                                        'json' => 'Données structurées (JSON)',
                                    ])
                                    ->live()
                                    ->required(),

                                TextInput::make('value_string')
                                    ->label('Valeur')
                                    ->statePath('value')
                                    ->visible(fn ($get) => $get('type') === 'string' || ! $get('type'))
                                    ->required(),

                                TextInput::make('value_integer')
                                    ->label('Valeur Numérique')
                                    ->statePath('value')
                                    ->numeric()
                                    ->visible(fn ($get) => $get('type') === 'integer')
                                    ->required(),

                                Toggle::make('value_boolean')
                                    ->label('Activer / Désactiver')
                                    ->statePath('value')
                                    ->visible(fn ($get) => $get('type') === 'boolean'),

                                ColorPicker::make('value_color')
                                    ->label('Couleur')
                                    ->statePath('value')
                                    ->visible(fn ($get) => $get('type') === 'color')
                                    ->required(),

                                Textarea::make('value_json')
                                    ->label('Valeur JSON')
                                    ->statePath('value')
                                    ->rows(5)
                                    ->visible(fn ($get) => $get('type') === 'json')
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
