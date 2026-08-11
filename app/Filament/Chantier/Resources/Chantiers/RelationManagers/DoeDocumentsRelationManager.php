<?php

namespace App\Filament\Chantier\Resources\Chantiers\RelationManagers;

use App\Enums\Chantiers\DoeDocumentCategory;
use App\Jobs\Chantiers\CompileDoeJob;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class DoeDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $title = 'Constitution du DOE';

    protected static string|BackedEnum|null $icon = Phosphor::FileZip;

    public static function getRelationshipName(): string
    {
        return 'doeDocuments';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fiche de documentation')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')->label('Nom')
                            ->label('Intitulé de la pièce')
                            ->required()
                            ->placeholder('ex: Notice technique pompe à chaleur Daikin'),
                        Select::make('category')
                            ->label('Type de pièce')
                            ->options(DoeDocumentCategory::class)
                            ->required()
                            ->native(false),
                        Toggle::make('is_validated')
                            ->label('Pièce validée pour le DOE final')
                            ->default(true)
                            ->onColor('success'),
                        SpatieMediaLibraryFileUpload::make('attachment')
                            ->label('Fichier joint')
                            ->collection('attachment')
                            ->required(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Nom')
                    ->label('Intitulé')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('category')
                    ->label('Catégorie')
                    ->badge()
                    ->color('primary'),
                ToggleColumn::make('is_validated')
                    ->label('Validé'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter une pièce'),
                Action::make('compile_doe')
                    ->label('Générer l\'archive DOE')
                    ->icon(Phosphor::FileZip)
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function () {
                        CompileDoeJob::dispatch($this->getOwnerRecord());

                        Notification::make()
                            ->title('Lancement de la compilation')
                            ->body('Le dossier est en cours de traitement en arrière-plan. Vous recevrez une alerte de téléchargement dès que l\'archive sera prête.')
                            ->info()
                            ->send();
                    }),
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
