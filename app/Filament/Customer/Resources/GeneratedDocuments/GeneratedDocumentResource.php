<?php

namespace App\Filament\Customer\Resources\GeneratedDocuments;

use App\Filament\Customer\Resources\GeneratedDocuments\Pages\ListGeneratedDocuments;
use App\Models\Core\GeneratedDocument;
use App\Models\Tiers\Contact;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
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

    public static function getEloquentQuery(): Builder
    {
        $contact = Contact::where('user_id', auth()->id())->first();

        $query = parent::getEloquentQuery();

        if (! $contact) {
            return $query->whereRaw('1 = 0');
        }

        $thirdPartyClass = get_class($contact->thirdParty);

        return $query->where('entity_type', $thirdPartyClass)
            ->where('entity_id', $contact->third_party_id);
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
