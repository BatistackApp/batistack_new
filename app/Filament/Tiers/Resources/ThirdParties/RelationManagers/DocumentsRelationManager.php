<?php

namespace App\Filament\Tiers\Resources\ThirdParties\RelationManagers;

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Models\Tiers\ThirdPartyDocument;
use App\Services\Core\SignatureService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

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
                    ->acceptedFileTypes(['application/pdf'])
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
                Action::make('request_signature')
                    ->label('Demander Signature')
                    ->icon(Phosphor::PenNib)
                    ->color('info')
                    ->visible(fn (ThirdPartyDocument $record) => $record->type === 'contrat_sous_traitance' &&
                        $record->signatures()->where('status', SignatureStatus::PENDING)->doesntExist() &&
                        $record->signatures()->where('status', SignatureStatus::SIGNED)->doesntExist()
                    )
                    ->action(function (ThirdPartyDocument $record, \App\Services\Core\SignatureService $service) {
                        $email = $record->thirdParty->email;
                        $name = $record->thirdParty->name;
                        $path = $record->getFirstMedia('third_party_documents')?->getPath();

                        if (!$email) {
                            Notification::make()->title('Erreur : Le tiers n\'a pas d\'adresse email')->danger()->send();
                            return;
                        }

                        $service->requestSignature(
                            model: $record,
                            type: \App\Enums\Core\SignatureType::AUTOGRAPH,
                            email: $email,
                            name: $name,
                            documentPath: $path
                        );
                        Notification::make()->title('Demande de signature envoyée par email')->success()->send();
                    }),
                Action::make('view_signature')
                    ->label('Voir Signature')
                    ->icon(Phosphor::CheckCircle)
                    ->color('success')
                    ->visible(fn (ThirdPartyDocument $record) => $record->signatures()->where('status', SignatureStatus::SIGNED)->exists())
                    ->modalContent(function (ThirdPartyDocument $record) {
                        $signature = $record->signatures()->where('status', SignatureStatus::SIGNED)->first();
                        return view('filament.tiers.signature-modal', ['signature' => $signature]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer'),
                Action::make('download')
                    ->label('Télécharger / Imprimer')
                    ->icon(Phosphor::DownloadSimple)
                    ->color('gray')
                    ->visible(fn (ThirdPartyDocument $record) => $record->hasMedia('third_party_documents'))
                    ->action(function (ThirdPartyDocument $record) {
                        $media = $record->getFirstMedia('third_party_documents');
                        if ($media) {
                            return response()->download($media->getPath(), $media->file_name);
                        }
                    }),
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
