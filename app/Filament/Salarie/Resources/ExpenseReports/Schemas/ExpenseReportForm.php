<?php

namespace App\Filament\Salarie\Resources\ExpenseReports\Schemas;

use Filament\Schemas\Schema;

class ExpenseReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Section::make('Informations de la note')
                    ->schema([
                        \Filament\Forms\Components\Select::make('month')
                            ->label('Mois')
                            ->options(
                                collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => \Carbon\Carbon::create()->day(1)->month($m)->translatedFormat('F')])->toArray()
                            )
                            ->disabled()
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('year')
                            ->label('Année')
                            ->disabled()
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('status')
                            ->label('Statut')
                            ->disabled(),
                    ])->columns(3),
            ]);
    }
}
