<?php

namespace App\Filament\Tiers\Resources;

use App\Filament\Tiers\Resources\ConsultationResource\Pages;
use App\Models\Tiers\Consultation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ConsultationResource extends Resource
{
    protected static ?string $model = Consultation::class;

    protected static string|\BackedEnum|null $navigationIcon = Phosphor::Handshake;

    protected static ?string $modelLabel = 'Appel d\'Offre / Consultation';

    protected static ?string $pluralModelLabel = 'Appels d\'Offres';

    protected static string|\UnitEnum|null $navigationGroup = 'Consultations';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('chantier_id')->label('Chantier')
                    ->relationship('chantier', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                DateTimePicker::make('deadline')
                    ->required(),
                Select::make('status')->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'published' => 'Publié',
                        'closed' => 'Clôturé',
                        'awarded' => 'Attribué',
                    ])
                    ->required()
                    ->default('draft'),
                RichEditor::make('description')->label('Description')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('chantier.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deadline')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'info',
                        'closed' => 'warning',
                        'awarded' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('offers_count')
                    ->counts('offers')
                    ->label('Offres reçues'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsultations::route('/'),
            'create' => Pages\CreateConsultation::route('/create'),
            'edit' => Pages\EditConsultation::route('/{record}/edit'),
        ];
    }
}
