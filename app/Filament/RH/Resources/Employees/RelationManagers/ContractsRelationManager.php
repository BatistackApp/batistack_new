<?php

namespace App\Filament\RH\Resources\Employees\RelationManagers;

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Enums\RH\ContractType;
use App\Models\RH\Contract;
use App\Services\Core\SignatureService;
use App\Services\RH\RHDocumentService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'contracts';

    protected static ?string $title = 'Historique des contrats';

    protected static string|BackedEnum|null $icon = Phosphor::FileText;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Détails du Contrat')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('type')->label('Type')
                            ->label('Type de contrat')
                            ->options(ContractType::class)
                            ->required()
                            ->native(false),
                        Select::make('job_title')
                            ->label('Intitulé du poste')
                            ->options(fn () => \Spatie\Permission\Models\Role::pluck('name', 'name'))
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')->label('Nom')
                                    ->label('Nom du poste/rôle')
                                    ->required()
                                    ->unique(\Spatie\Permission\Models\Role::class, 'name', ignoreRecord: false)
                            ])
                            ->createOptionUsing(function (array $data) {
                                \Illuminate\Support\Facades\Gate::authorize('create', \Spatie\Permission\Models\Role::class);
                                $role = \Spatie\Permission\Models\Role::create(['name' => $data['name'], 'guard_name' => 'web']);
                                return $role->name;
                            })
                            ->required(),
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
                        Select::make('payroll_contribution_profile_id')
                            ->label('Profil de cotisations')
                            ->relationship('payrollContributionProfile', 'name')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->helperText('Définit les taux de cotisations (ex: Bâtiment ETAM, Ouvrier).'),
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
                TextColumn::make('type')->label('Type')
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
                    ->badge(),
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
                    ->action(function (Contract $record, RHDocumentService $service) {
                        $relativePath = 'documents/rh/contrat_'.$record->employee->registration_number.'.pdf';
                        if (!\Illuminate\Support\Facades\Storage::disk(\App\Services\Core\DocumentService::getDisk())->exists($relativePath)) {
                            $relativePath = $service->generateContract($record);
                        }
                        return $service->download($relativePath);
                    }),
                Action::make('request_signature')
                    ->icon(Phosphor::PenNib)
                    ->color('info')
                    ->visible(fn (Contract $record) => $record->signature_status === SignatureStatus::PENDING)
                    ->action(function (Contract $record, SignatureService $signatureService, RHDocumentService $documentService) {
                        $email = $record->employee->email;
                        $name = $record->employee->full_name;
                        $relativePath = 'documents/rh/contrat_'.$record->employee->registration_number.'.pdf';

                        if (! $email) {
                            Notification::make()->title('Erreur : Le salarié n\'a pas d\'adresse email')->danger()->send();

                            return;
                        }

                        $disk = \App\Services\Core\DocumentService::getDisk();
                        if (!\Illuminate\Support\Facades\Storage::disk($disk)->exists($relativePath)) {
                            $relativePath = $documentService->generateContract($record);
                        }
                        
                        $absolutePath = \Illuminate\Support\Facades\Storage::disk($disk)->path($relativePath);

                        $signatureService->requestSignature(
                            model: $record,
                            type: SignatureType::AUTOGRAPH,
                            email: $email,
                            name: $name,
                            documentPath: $absolutePath,
                        );

                        Notification::make()->title('Demande de signature envoyée par email')->success()->send();
                    })
                    ->label('Demander Signature'),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
