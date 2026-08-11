<?php

namespace App\Filament\RH\Resources\Employees\RelationManagers;

use App\Enums\RH\QualificationType;
use BackedEnum;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class QualificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'qualifications';
    protected static ?string $title = 'Habilitations & Compétences';
    protected static string | BackedEnum | null $icon = Phosphor::ShieldCheck;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')->label('Type')
                    ->label('Type d\'habilitation')
                    ->options(QualificationType::class)
                    ->required()
                    ->native(false),
                TextInput::make('label')
                    ->label('Libellé précis')
                    ->required()
                    ->placeholder('ex: CACES R482 Catégorie F'),
                TextInput::make('reference_number')
                    ->label('N° Certificat / Référence'),
                Grid::make(2)
                    ->schema([
                        DatePicker::make('obtained_at')
                            ->label('Date d\'obtention')
                            ->native(false),
                        DatePicker::make('expires_at')
                            ->label('Date d\'expiration')
                            ->required()
                            ->native(false),
                    ]),
                SpatieMediaLibraryFileUpload::make('document')
                    ->label('Copie du justificatif (PDF/Image)')
                    ->collection('qualification_docs')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                IconColumn::make('type')->label('Type')
                    ->label('')
                    ->icon(fn ($state) => $state->getIcon()),
                TextColumn::make('label')
                    ->label('Habilitation')
                    ->description(fn ($record) => $record->reference_number)
                    ->tooltip(fn ($record) => $record->label->getDescription())
                    ->searchable(),
                TextColumn::make('obtained_at')
                    ->label('Obtenue le')
                    ->date('d/m/Y')
                    ->toggleable(),
                TextColumn::make('expires_at')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->color(fn ($state) => $state->isPast() ? 'danger' : ($state->diffInDays(now()) < 30 ? 'warning' : 'success'))
                    ->weight('bold'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter une compétence'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
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
