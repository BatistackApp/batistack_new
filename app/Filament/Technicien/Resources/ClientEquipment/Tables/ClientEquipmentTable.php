<?php

namespace App\Filament\Technicien\Resources\ClientEquipment\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientEquipmentTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('thirdParty.name')
                    ->label('Client')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),

                TextColumn::make('brand')
                    ->label('Marque')
                    ->searchable(),

                TextColumn::make('serial_number')
                    ->label('N° Série')
                    ->searchable()
                    ->fontFamily('mono'),

                TextColumn::make('installation_date')
                    ->label('Installation')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('interventions_count')
                    ->label('Interventions')
                    ->counts('interventions')
                    ->badge()
                    ->color('info'),
            ])
            ->defaultSort('name', 'asc')
            ->paginated([10, 20, 50]);
    }
}
