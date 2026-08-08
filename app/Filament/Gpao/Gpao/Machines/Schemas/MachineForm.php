<?php

namespace App\Filament\Gpao\Gpao\Machines\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MachineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('reference'),
                \Filament\Forms\Components\Select::make('status')
                    ->options(\App\Enums\Gpao\MachineStatus::class)
                    ->required()
                    ->default(\App\Enums\Gpao\MachineStatus::OPERATIONAL),
                TextInput::make('usage_hours')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('maintenance_interval_hours')
                    ->required()
                    ->numeric()
                    ->default(50.0),
            ]);
    }
}
