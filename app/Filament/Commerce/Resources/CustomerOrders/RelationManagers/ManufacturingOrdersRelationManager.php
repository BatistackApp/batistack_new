<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ManufacturingOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'manufacturingOrders';

    protected static ?string $title = 'Ordres de Fabrication';

    protected static ?string $modelLabel = 'OF';

    protected static ?string $pluralModelLabel = 'OFs';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Read-only relation manager
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable(),

                Tables\Columns\TextColumn::make('item.name')
                    ->label('Article')
                    ->searchable(),

                Tables\Columns\TextColumn::make('quantity_planned')
                    ->label('Qté. Prévue')
                    ->numeric(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view_of')
                    ->label('Voir l\'OF')
                    ->icon('phosphor-eye')
                    ->url(fn ($record): string => route('filament.gpao.resources.manufacturing-orders.edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ]);
    }
}
