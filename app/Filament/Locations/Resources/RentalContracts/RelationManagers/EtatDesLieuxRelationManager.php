<?php

namespace App\Filament\Locations\Resources\RentalContracts\RelationManagers;

use App\Enums\Locations\RentalConditionReportType;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class EtatDesLieuxRelationManager extends RelationManager
{
    protected static string $relationship = 'conditionReports';

    protected static ?string $title = 'État des lieux (matériel loué)';

    protected static string|null|\BackedEnum $icon = Phosphor::Camera;

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('N°')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                ImageColumn::make('photos')
                    ->label('Photos')
                    ->collection('photos')
                    ->circular()
                    ->stacked()
                    ->limit(3),
                TextColumn::make('comment')
                    ->label('Commentaire')
                    ->limit(40)
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('captured_at')
                    ->label('Horodatage')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('isSigned')
                    ->label('Signature')
                    ->state(fn ($record) => $record->isSigned() ? 'Signé' : 'Non signé')
                    ->badge()
                    ->color(fn ($record) => $record->isSigned() ? 'success' : 'gray'),
                TextColumn::make('latitude')
                    ->label('GPS')
                    ->state(fn ($record) => $record->latitude !== null ? $record->latitude.', '.$record->longitude : '—'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(RentalConditionReportType::class),
            ])
            ->defaultSort('captured_at', 'desc');
    }
}
