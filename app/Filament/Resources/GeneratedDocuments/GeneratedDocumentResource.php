<?php

namespace App\Filament\Resources\GeneratedDocuments;

use App\Filament\Resources\GeneratedDocuments\Pages\ListGeneratedDocuments;
use App\Filament\Resources\GeneratedDocuments\Pages\ViewGeneratedDocument;
use App\Filament\Resources\GeneratedDocuments\Tables\GeneratedDocumentTable;
use App\Models\Core\GeneratedDocument;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use UnitEnum;

class GeneratedDocumentResource extends Resource
{
    protected static ?string $model = GeneratedDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Files;

    protected static string|UnitEnum|null $navigationGroup = 'Gestion Documentaire';

    protected static ?string $navigationLabel = 'GED - Documents';

    protected static ?string $navigationBreadcrumb = 'Documents';

    protected static ?int $navigationSort = 1;

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextEntry::make('module')
                    ->label('Module')
                    ->badge()
                    ->color(fn (GeneratedDocument $record): string => $record->module_color)
                    ->formatStateUsing(fn (GeneratedDocument $record): string => $record->module_label),

                TextEntry::make('type')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                TextEntry::make('file_name')
                    ->label('Nom du fichier'),

                TextEntry::make('file_path')
                    ->label('Chemin')
                    ->copyable()
                    ->fontFamily('mono'),

                TextEntry::make('entity_type')
                    ->label('Entité liée')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—'),

                TextEntry::make('file_size')
                    ->label('Taille')
                    ->formatStateUsing(fn (?GeneratedDocument $record): string => $record->formatted_size),

                TextEntry::make('generated_at')
                    ->label('Généré le')
                    ->dateTime('d/m/Y H:i'),

                TextEntry::make('generatedBy.name')
                    ->label('Généré par')
                    ->formatStateUsing(fn (?string $state): string => $state ?? 'Système'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return GeneratedDocumentTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGeneratedDocuments::route('/'),
            'view' => ViewGeneratedDocument::route('/{record}'),
        ];
    }
}
