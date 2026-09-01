<?php

namespace App\Filament\RH\Resources\Employees\RelationManagers;

use App\Enums\Core\SignatureStatus;
use App\Enums\Core\SignatureType;
use App\Enums\RH\ContractType;
use App\Enums\RH\EmployeeCategory;
use App\Enums\RH\TerminationType;
use App\Models\RH\Contract;
use App\Services\Core\DocumentService;
use App\Services\Core\SignatureService;
use App\Services\RH\ContractTerminationService;
use App\Services\RH\RHDocumentService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
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
                        Select::make('category')
                            ->label('Catégorie (Statut)')
                            ->options(EmployeeCategory::class)
                            ->required()
                            ->default('ouvrier')
                            ->native(false),
                        Select::make('job_title')
                            ->label('Intitulé du poste')
                            ->options(fn () => Role::pluck('name', 'name'))
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')->label('Nom')
                                    ->label('Nom du poste/rôle')
                                    ->required()
                                    ->unique(Role::class, 'name', ignoreRecord: false),
                            ])
                            ->createOptionUsing(function (array $data) {
                                Gate::authorize('create', Role::class);
                                $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);

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
                TextColumn::make('category')
                    ->label('Catégorie')
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
                TextColumn::make('terminated_at')
                    ->label('Rompus le')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->color('danger'),
            ])
            ->filters([
                Filament\Tables\Filters\Filter::make('terminated')
                    ->label('Rompus')
                    ->query(fn ($query) => $query->whereNotNull('terminated_at'))
                    ->toggle(),
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
                        if (! Storage::disk(DocumentService::getDisk())->exists($relativePath)) {
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

                        $disk = DocumentService::getDisk();
                        if (! Storage::disk($disk)->exists($relativePath)) {
                            $relativePath = $documentService->generateContract($record);
                        }

                        $signatureService->requestSignature(
                            model: $record,
                            type: SignatureType::AUTOGRAPH,
                            email: $email,
                            name: $name,
                            documentPath: $relativePath,
                        );

                        Notification::make()->title('Demande de signature envoyée par email')->success()->send();
                    })
                    ->label('Demander Signature'),
                ActionGroup::make([
                    Action::make('trial_end')
                        ->label('Rupture Période d\'Essai')
                        ->icon(Phosphor::FileMinus)
                        ->color('danger')
                        ->visible(fn (Contract $record) => $record->trial_end_date && $record->trial_end_date->isFuture())
                        ->action(fn (Contract $record, RHDocumentService $service) => $service->download($service->generateTrialPeriodEndLetter($record))),
                    Action::make('cdd_terminate')
                        ->label('Avenant Rupture CDD')
                        ->icon(Phosphor::FileX)
                        ->color('danger')
                        ->visible(fn (Contract $record) => $record->type === ContractType::CDD)
                        ->form([
                            DatePicker::make('termination_date')
                                ->label('Date de rupture négociée')
                                ->required(),
                        ])
                        ->action(fn (Contract $record, array $data, RHDocumentService $service) => $service->download($service->generateCddEarlyTermination($record, Carbon::parse($data['termination_date'])))),
                    Action::make('cdi_terminate')
                        ->label('Rupture CDI')
                        ->icon(Phosphor::FileX)
                        ->color('danger')
                        ->visible(fn (Contract $record) => $record->type === ContractType::CDI && $record->isActive())
                        ->form(fn (Action $action) => [
                            Select::make('termination_type')
                                ->label('Type de rupture')
                                ->options(TerminationType::class)
                                ->required()
                                ->native(false),
                            DatePicker::make('terminated_at')
                                ->label('Date de notification')
                                ->required()
                                ->default(now())
                                ->native(false),
                            TextInput::make('termination_reason')
                                ->label('Motif')
                                ->required(),
                            TextInput::make('termination_amount')
                                ->label('Indemnité (€)')
                                ->numeric()
                                ->prefix('€')
                                ->minValue(0),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Rompre le CDI')
                        ->modalDescription('Cette action va rompre le contrat et générer les documents de fin de contrat.')
                        ->action(function (Contract $record, array $data, ContractTerminationService $terminationService, RHDocumentService $documentService) {
                            $record = $terminationService->terminate(
                                contract: $record,
                                type: TerminationType::from($data['termination_type']),
                                reason: $data['termination_reason'],
                                terminatedAt: Carbon::parse($data['terminated_at']),
                                amount: $data['termination_amount'] ?? null,
                            );

                            $documentService->download($documentService->generateCdiTerminationLetter($record, $terminationService));

                            Notification::make()
                                ->title('CDI rompu')
                                ->body("Le contrat de {$record->job_title} a été rompu le {$record->terminated_at->format('d/m/Y')}.")
                                ->success()
                                ->send();
                        }),
                ])->label('Documents de rupture')->icon(Phosphor::Files),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
