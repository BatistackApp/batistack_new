<?php

namespace App\Filament\RH\Resources\Employees\RelationManagers;

use App\Enums\RH\ContractType;
use App\Models\RH\Contract;
use App\Services\RH\RHDocumentService;
use BackedEnum;
use Filament\Actions\Action;
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
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'contracts';
    protected static ?string $title = 'Historique des contrats';
    protected static string | BackedEnum | null $icon = Phosphor::FileText;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Détails du Contrat')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('type')
                            ->label('Type de contrat')
                            ->options(ContractType::class)
                            ->required()
                            ->native(false),
                        TextInput::make('job_title')
                            ->label('Intitulé du poste')
                            ->required()
                            ->placeholder('ex: Couvreur N3P2'),
                        DatePicker::make('start_date')
                            ->label('Date d\'entrée')
                            ->required()
                            ->native(false),
                        DatePicker::make('end_date')
                            ->label('Date de fin')
                            ->helperText('Laisser vide pour un CDI')
                            ->native(false),
                        TextInput::make('hourly_rate')
                            ->label('Taux horaire brut')
                            ->numeric()
                            ->prefix('€')
                            ->required()
                            ->step(0.0001),
                        TextInput::make('weekly_hours')
                            ->label('Heures hebdomadaires')
                            ->numeric()
                            ->default(35)
                            ->suffix('h'),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('job_title')
            ->columns([
                TextColumn::make('job_title')
                    ->label('Poste')
                    ->weight('bold'),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('start_date')
                    ->label('Du')
                    ->date('d/m/Y'),
                TextColumn::make('end_date')
                    ->label('Au')
                    ->date('d/m/Y')
                    ->placeholder('En cours'),
                TextColumn::make('hourly_rate')
                    ->label('Taux H.')
                    ->money('EUR')
                    ->color('gray'),
                TextColumn::make('signature_status')
                    ->label('Signature')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'sent' => 'warning',
                        'signed' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->label('Nouveau contrat'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('print_contract')
                    ->label('Imprimer')
                    ->icon(Phosphor::Printer)
                    ->action(fn (Contract $record, RHDocumentService $service) => \Illuminate\Support\Facades\Storage::disk('public')->download($service->generateContract($record))),
                Action::make('send_docuseal')
                    ->label('Envoyer pour Signature')
                    ->icon(Phosphor::PaperPlaneRight)
                    ->color('info')
                    ->visible(fn (Contract $record) => $record->signature_status === 'pending')
                    ->action(function (Contract $record, \App\Services\Core\DocuSealService $service) {
                        // Assuming template ID 1 is the generic contract template in DocuSeal
                        $success = $service->sendContractForSignature($record, 1);
                        
                        if ($success) {
                            \Filament\Notifications\Notification::make()->title('Contrat envoyé sur DocuSeal')->success()->send();
                        } else {
                            \Filament\Notifications\Notification::make()->title('Erreur lors de l\'envoi')->danger()->send();
                        }
                    }),
                Action::make('check_docuseal')
                    ->label('Vérifier Signature')
                    ->icon(Phosphor::ArrowsClockwise)
                    ->color('warning')
                    ->visible(fn (Contract $record) => $record->signature_status === 'sent')
                    ->action(function (Contract $record, \App\Services\Core\DocuSealService $service) {
                        $success = $service->checkAndDownloadSignedContract($record);
                        if ($success) {
                            \Filament\Notifications\Notification::make()->title('Contrat signé récupéré !')->success()->send();
                        } else {
                            \Filament\Notifications\Notification::make()->title('Contrat toujours en attente')->info()->send();
                        }
                    }),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
