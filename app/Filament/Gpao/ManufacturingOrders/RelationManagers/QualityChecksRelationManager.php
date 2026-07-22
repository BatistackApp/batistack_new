<?php

namespace App\Filament\Gpao\ManufacturingOrders\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class QualityChecksRelationManager extends RelationManager
{
    protected static string $relationship = 'qualityChecks';
    protected static ?string $title = 'Contrôles Qualité';
    protected static ?string $modelLabel = 'contrôle qualité';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('status'),
                Forms\Components\Textarea::make('notes'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
                Tables\Columns\TextColumn::make('inspector.name')
                    ->label('Inspecteur')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Résultat')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'passed' => 'Validé',
                        'failed' => 'Refusé',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'passed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50),
                Tables\Columns\TextColumn::make('checked_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('checked_at', 'desc');
    }
}
