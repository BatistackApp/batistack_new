<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DisposalRelationManager extends RelationManager
{
    protected static string $relationship = 'disposal';

    protected static ?string $title = 'Cession / Rebut';

    protected static ?string $modelLabel = 'Cession';

    protected static ?string $pluralModelLabel = 'Cessions';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reason')
            ->columns([
                TextColumn::make('disposal_date')
                    ->label('Date de cession')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('Motif')
                    ->searchable(),
                TextColumn::make('sale_price')
                    ->label('Prix de cession')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('profit_or_loss')
                    ->label('Résultat')
                    ->money('EUR')
                    ->sortable()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => ($state >= 0 ? '+' : '').number_format($state, 2, ',', ' ').' €'),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
