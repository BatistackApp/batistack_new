<?php

namespace App\Filament\Resources\Units\Schemas;

use App\Enums\Core\UnitType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom de l\'unité')
                            ->required(),

                        TextInput::make('symbol')
                            ->label('Symbole')
                            ->maxLength(10)
                            ->required(),

                        Select::make('type')
                            ->label('Type de mesure')
                            ->options(UnitType::class)
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }
}
