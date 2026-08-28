<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetTransfers\Schemas;

use App\Models\Immobilisation\FixedAsset;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AssetTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations du transfert')
                    ->schema([
                        Select::make('fixed_asset_id')
                            ->label('Immobilisation')
                            ->relationship('fixedAsset', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $asset = FixedAsset::find($state);
                                    if ($asset) {
                                        $set('from_chantier_id', $asset->chantier_id);
                                    }
                                }
                            }),
                        Select::make('from_chantier_id')
                            ->label('Chantier d\'origine')
                            ->relationship('fromChantier', 'name')
                            ->disabled()
                            ->dehydrated(),
                        Select::make('to_chantier_id')
                            ->label('Chantier de destination')
                            ->relationship('toChantier', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('transfer_date')
                            ->label('Date de transfert prévue')
                            ->required()
                            ->default(now()),
                        Hidden::make('requested_by')
                            ->default(fn () => auth()->id()),
                        Textarea::make('notes')->label('Notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
