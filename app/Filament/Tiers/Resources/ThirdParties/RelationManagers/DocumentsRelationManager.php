<?php

namespace App\Filament\Tiers\Resources\ThirdParties\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents de Conformité';

    protected static ?string $modelLabel = 'Document';

    protected static ?string $pluralModelLabel = 'Documents';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->options([
                        'kbis' => 'Kbis',
                        'urssaf' => 'Attestation URSSAF',
                        'decennale' => 'Assurance Décennale',
                        'autre' => 'Autre',
                    ])
                    ->required(),
                DatePicker::make('expiration_date')
                    ->label('Date d\'expiration')
                    ->required(),
                Select::make('status')
                    ->options([
                        'valid' => 'Valide',
                        'expired' => 'Expiré',
                    ])
                    ->default('valid')
                    ->required(),
                SpatieMediaLibraryFileUpload::make('document')
                    ->label('Fichier PDF/Image')
                    ->collection('third_party_documents')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'kbis' => 'Kbis',
                        'urssaf' => 'Attestation URSSAF',
                        'decennale' => 'Assurance Décennale',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('expiration_date')
                    ->label('Date d\'expiration')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'valid' => 'success',
                        'expired' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
