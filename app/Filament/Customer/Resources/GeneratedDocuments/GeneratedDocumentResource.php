<?php

namespace App\Filament\Customer\Resources\GeneratedDocuments;

use App\Filament\Customer\Resources\GeneratedDocuments\Pages\ListGeneratedDocuments;
use App\Filament\Customer\Resources\GeneratedDocuments\Pages\ViewGeneratedDocument;
use App\Models\Core\GeneratedDocument;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use UnitEnum;

class GeneratedDocumentResource extends Resource
{
    protected static ?string $model = GeneratedDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Files;

    protected static string|UnitEnum|null $navigationGroup = 'Documents';

    protected static ?string $navigationLabel = 'Mes Documents';

    protected static ?string $navigationBreadcrumb = 'Documents';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canUpdate($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextEntry::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                TextEntry::make('file_name')
                    ->label('Nom du fichier'),

                TextEntry::make('file_size')
                    ->label('Taille')
                    ->formatStateUsing(fn (?GeneratedDocument $record): string => $record->formatted_size),

                TextEntry::make('generated_at')
                    ->label('Généré le')
                    ->dateTime('d/m/Y H:i'),

                TextEntry::make('module')
                    ->label('Module')
                    ->formatStateUsing(fn (GeneratedDocument $record): string => $record->module_label),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $thirdPartyIds = Contact::where('user_id', auth()->id())
            ->pluck('third_party_id');

        $query = parent::getEloquentQuery();

        if ($thirdPartyIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('entity_type', ThirdParty::class)
            ->whereIn('entity_id', $thirdPartyIds);
    }

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
            ->recordActions([
                ViewAction::make()
                    ->label('Voir')
                    ->icon(Phosphor::Eye)
                    ->url(fn (GeneratedDocument $record): string => static::getUrl('view', ['record' => $record])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGeneratedDocuments::route('/'),
            'view' => ViewGeneratedDocument::route('/{record}'),
        ];
    }
}
