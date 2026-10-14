<?php

namespace App\Filament\Subcontractor\Resources\PrivateMediaDocuments;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Commerce\SubcontractorSituation;
use App\Models\Tiers\Contact;
use App\Models\Tiers\ThirdParty;
use App\Models\Tiers\ThirdPartyDocument;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use BackedEnum;
use UnitEnum;

class PrivateMediaDocumentResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Files;

    protected static string|UnitEnum|null $navigationGroup = 'Documents';

    protected static ?string $navigationLabel = 'Documents contractuels';

    protected static ?string $modelLabel = 'document';

    protected static ?string $pluralModelLabel = 'Documents contractuels';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $thirdPartyIds = Contact::query()
            ->where('user_id', auth()->id())
            ->whereHas('thirdParty', fn (Builder $query) => $query->where('type', ThirdPartyType::SUBCONTRACTOR))
            ->select('third_party_id');

        $thirdPartyDocumentIds = ThirdPartyDocument::query()
            ->whereIn('third_party_id', $thirdPartyIds)
            ->select('id');

        $situationIds = SubcontractorSituation::query()
            ->whereIn('subcontractor_id', $thirdPartyIds)
            ->select('id');

        return parent::getEloquentQuery()
            ->where(function (Builder $query) use ($thirdPartyIds, $thirdPartyDocumentIds, $situationIds): void {
                $query->where(function (Builder $documents) use ($thirdPartyDocumentIds): void {
                    $documents->where('model_type', ThirdPartyDocument::class)
                        ->whereIn('model_id', $thirdPartyDocumentIds);
                })->orWhere(function (Builder $companyMedia) use ($thirdPartyIds): void {
                    $companyMedia->where('model_type', ThirdParty::class)
                        ->whereIn('model_id', $thirdPartyIds);
                })->orWhere(function (Builder $invoiceMedia) use ($situationIds): void {
                    $invoiceMedia->where('model_type', SubcontractorSituation::class)
                        ->whereIn('model_id', $situationIds);
                });
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_name')
                    ->label('Document')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('collection_name')
                    ->label('Catégorie')
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Ajouté le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('preview')
                    ->label('Voir')
                    ->icon(Phosphor::Eye)
                    ->url(fn (Media $record): string => route('subcontractor.documents.media.preview', ['media' => $record->getKey()]))
                    ->openUrlInNewTab(),
                Action::make('download')
                    ->label('Télécharger')
                    ->icon(Phosphor::DownloadSimple)
                    ->url(fn (Media $record): string => route('subcontractor.documents.media.download', ['media' => $record->getKey()]))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrivateMediaDocuments::route('/'),
        ];
    }
}
