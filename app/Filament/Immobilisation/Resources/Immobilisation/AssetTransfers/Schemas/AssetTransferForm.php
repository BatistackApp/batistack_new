<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetTransfers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AssetTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Section::make('Informations du transfert')
                    ->schema([
                        \Filament\Forms\Components\Select::make('fixed_asset_id')
                            ->label('Immobilisation')
                            ->relationship('fixedAsset', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (\Filament\Forms\Set $set, $state) {
                                if ($state) {
                                    $asset = \App\Models\Immobilisation\FixedAsset::find($state);
                                    if ($asset) {
                                        $set('from_chantier_id', $asset->chantier_id);
                                    }
                                }
                            }),
                        \Filament\Forms\Components\Select::make('from_chantier_id')
                            ->label('Chantier d\'origine')
                            ->relationship('fromChantier', 'name')
                            ->disabled()
                            ->dehydrated(),
                        \Filament\Forms\Components\Select::make('to_chantier_id')
                            ->label('Chantier de destination')
                            ->relationship('toChantier', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        \Filament\Forms\Components\DatePicker::make('transfer_date')
                            ->label('Date de transfert prévue')
                            ->required()
                            ->default(now()),
                        \Filament\Forms\Components\Hidden::make('requested_by')
                            ->default(fn () => auth()->id()),
                        \Filament\Forms\Components\Textarea::make('notes')->label('Notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
