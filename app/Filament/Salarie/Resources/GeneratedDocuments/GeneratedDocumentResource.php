<?php

namespace App\Filament\Salarie\Resources\GeneratedDocuments;

use App\Filament\Salarie\Resources\GeneratedDocuments\Pages\ListGeneratedDocuments;
use App\Models\Core\GeneratedDocument;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use UnitEnum;

class GeneratedDocumentResource extends Resource
{
    protected static ?string $model = GeneratedDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Files;

    protected static string|UnitEnum|null $navigationGroup = 'Mes Documents';

    protected static ?string $navigationLabel = 'Mes Documents';

    protected static ?string $navigationBreadcrumb = 'Mes Documents';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('file_name')
                    ->label('Document')
                    ->searchable()
                    ->limit(50)
                    ->wrap(),

                TextColumn::make('file_size')
                    ->label('Taille')
                    ->formatStateUsing(fn (?GeneratedDocument $record): string => $record->formatted_size)
                    ->sortable(),

                TextColumn::make('generated_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('generated_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->where('module', 'rh'))
            ->recordActions([
                ViewAction::make()
                    ->label('Voir')
                    ->icon(Phosphor::Eye),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGeneratedDocuments::route('/'),
        ];
    }
}
