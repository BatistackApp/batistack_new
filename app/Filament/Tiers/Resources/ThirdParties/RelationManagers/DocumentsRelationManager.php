<?php

namespace App\Filament\Tiers\Resources\ThirdParties\RelationManagers;

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Enums\Tiers\ThirdPartyDocumentStatus;
use App\Enums\Tiers\ThirdPartyDocumentType;
use App\Enums\Tiers\ThirdPartyType;
use App\Jobs\Tiers\CollectLegalDocumentsJob;
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
                Select::make('type')->label('Type')
                    ->options(ThirdPartyDocumentType::class)
                    ->required(),
                DatePicker::make('expiration_date')
                    ->label('Date d\'expiration')
                    ->required(),
                Select::make('status')->label('Statut')
                    ->options(ThirdPartyDocumentStatus::class)
                    ->default(ThirdPartyDocumentStatus::VALID)
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
                Tables\Columns\TextColumn::make('type')->label('Type')
                    ->label('Type'),
                Tables\Columns\TextColumn::make('expiration_date')
                    ->label('Date d\'expiration')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('collect_legal_documents')
                    ->label('Collecter les documents légaux')
                    ->icon(Phosphor::DownloadSimple)
                    ->color('info')
                    ->visible(function () {
                        $thirdParty = $this->getOwnerRecord();

                        return $thirdParty->siren && in_array($thirdParty->type, [ThirdPartyType::SUBCONTRACTOR, ThirdPartyType::CLIENT]);
                    })
                    ->action(function () {
                        $thirdParty = $this->getOwnerRecord();
                        CollectLegalDocumentsJob::dispatch($thirdParty);

                        Notification::make()
                            ->success()
                            ->title('Collecte des documents légaux lancée en arrière-plan')
                            ->send();
                    }),
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('request_signature')
                    ->label('Demander Signature')
                    ->icon(Phosphor::PenNib)
                    ->color('info')
                    ->visible(fn (ThirdPartyDocument $record) => $record->type === ThirdPartyDocumentType::CONTRAT_SOUS_TRAITANCE &&
                        $record->signatures()->where('status', SignatureStatus::PENDING)->doesntExist() &&
                        $record->signatures()->where('status', SignatureStatus::SIGNED)->doesntExist()
                    )
                    ->form([
                        Filament\Forms\Components\Toggle::make('is_multi')
                            ->label('Signature multi-signataires')
                            ->default(false)
                            ->live(),
                        Filament\Forms\Components\TextInput::make('name')
                            ->label('Nom du signataire')
                            ->required()
                            ->default(fn (ThirdPartyDocument $record) => $record->thirdParty->name)
                            ->visible(fn (Filament\Forms\Components\Get $get) => ! $get('is_multi')),
                        Filament\Forms\Components\TextInput::make('email')
                            ->label('Email du signataire')
                            ->email()
                            ->required()
                            ->default(fn (ThirdPartyDocument $record) => $record->thirdParty->email)
                            ->visible(fn (Filament\Forms\Components\Get $get) => ! $get('is_multi')),
                        Filament\Forms\Components\Repeater::make('signers')
                            ->label('Signataires')
                            ->schema([
                                Filament\Forms\Components\TextInput::make('name')
                                    ->label('Nom')
                                    ->required(),
                                Filament\Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required(),
                                Filament\Forms\Components\Select::make('role')
                                    ->label('Rôle')
                                    ->options([
                                        'Signataire' => 'Signataire',
                                        'Client' => 'Client',
                                        'Manager' => 'Manager',
                                        'Sous-traitant' => 'Sous-traitant',
                                        'Autre' => 'Autre',
                                    ])
                                    ->default('Signataire'),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Ajouter un signataire')
                            ->visible(fn (Filament\Forms\Components\Get $get) => $get('is_multi'))
                            ->required(fn (Filament\Forms\Components\Get $get) => $get('is_multi')),
                    ])
                    ->action(function (ThirdPartyDocument $record, array $data, SignatureService $service) {
                        $path = $record->getFirstMedia('third_party_documents')?->getPath();

                        if ($data['is_multi'] ?? false) {
                            $service->requestMultiSignature(
                                model: $record,
                                type: SignatureType::AUTOGRAPH,
                                signers: $data['signers'],
                                documentPath: $path
                            );
                        } else {
                            $email = $data['email'];
                            $name = $data['name'];

                            if (! $email) {
                                Notification::make()->title('Erreur : Le tiers n\'a pas d\'adresse email')->danger()->send();

                                return;
                            }

                            $service->requestSignature(
                                model: $record,
                                type: SignatureType::AUTOGRAPH,
                                email: $email,
                                name: $name,
                                documentPath: $path
                            );
                        }

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
