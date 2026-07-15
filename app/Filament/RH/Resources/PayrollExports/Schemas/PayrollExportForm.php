<?php

namespace App\Filament\RH\Resources\PayrollExports\Schemas;

use App\Enums\RH\PayrollExportStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PayrollExportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('month')
                    ->required()
                    ->numeric(),
                TextInput::make('year')
                    ->required()
                    ->numeric(),
                Select::make('status')
                    ->options(PayrollExportStatus::class)
                    ->default('draft')
                    ->required(),
                TextInput::make('total_employees')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
