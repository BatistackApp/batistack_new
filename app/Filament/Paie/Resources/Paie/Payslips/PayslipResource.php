<?php

namespace App\Filament\Paie\Resources\Paie\Payslips;

use App\Enums\Paie\DsnStatus;
use App\Enums\Paie\PayslipStatus;
use App\Filament\Paie\Resources\Paie\Payslips\Pages\CreatePayslip;
use App\Filament\Paie\Resources\Paie\Payslips\Pages\EditPayslip;
use App\Filament\Paie\Resources\Paie\Payslips\Pages\ListPayslips;
use App\Filament\Paie\Resources\Paie\Payslips\Pages\ViewPayslip;
use App\Filament\Paie\Resources\Paie\SalaryPaymentRuns\SalaryPaymentRunResource;
use App\Jobs\Paie\DistributePayslipJob;
use App\Jobs\Paie\InitiateSalaryPaymentRunJob;
use App\Jobs\Paie\SendPayslipToDigiposteJob;
use App\Models\Banque\BankAccount;
use App\Models\Paie\Payslip;
use App\Models\RH\Employee;
use App\Notifications\Paie\DsnExportedNotification;
use App\Services\Paie\AccountingExportService;
use App\Services\Paie\DsnExportService;
use App\Services\Paie\PayslipLockService;
use App\Services\Paie\PayslipPdfService;
use App\Services\Paie\SalaryPaymentService;
use App\Services\Paie\SepaExportService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Number;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PayslipResource extends Resource
{
    protected static ?string $model = Payslip::class;

    protected static ?string $modelLabel = 'Fiche de paie';

    protected static ?string $pluralModelLabel = 'Fiches de paie';

    protected static string|null|\UnitEnum $navigationGroup = 'Gestion de la Paie';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('employee_id')
                    ->label('Employé')
                    ->relationship('employee', 'last_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->last_name.' '.$record->first_name)
                    ->searchable(['last_name', 'first_name'])
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if (! $state) {
                            return;
                        }

                        $employee = Employee::with('currentContract')->find($state);
                        if (! $employee) {
                            return;
                        }

                        $contract = $employee->currentContract;
                        if ($contract) {
                            $set('hourly_rate', $contract->hourly_rate);
                            // weekly_hours -> base mensuelle (heures hebdo * 52 / 12)
                            $baseHours = round(($contract->weekly_hours * 52) / 12, 2);
                            $set('base_hours', $baseHours);
                        }
                    }),
                Forms\Components\TextInput::make('period')
                    ->label('Période (YYYY-MM)')
                    ->required()
                    ->default(now()->format('Y-m'))
                    ->maxLength(255),
                Forms\Components\TextInput::make('base_hours')
                    ->label('Heures de base (mensuel)')
                    ->required()
                    ->numeric()
                    ->helperText('Pré-rempli depuis le contrat en cours de l\'employé.'),
                Forms\Components\TextInput::make('hourly_rate')
                    ->label('Taux horaire (€)')
                    ->required()
                    ->numeric()
                    ->helperText('Pré-rempli depuis le contrat en cours de l\'employé.'),
                Forms\Components\Select::make('status')->label('Statut')
                    ->options(PayslipStatus::class)
                    ->required()
                    ->default(PayslipStatus::DRAFT),

                Forms\Components\Repeater::make('custom_bonuses')
                    ->label('Primes et éléments variables exceptionnels')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('label')
                            ->label('Libellé de la prime')
                            ->required(),
                        Forms\Components\TextInput::make('amount')->label('Montant')
                            ->label('Montant (€)')
                            ->numeric()
                            ->required(),
                        Forms\Components\Toggle::make('is_taxable')
                            ->label('Soumis à cotisations (Brut)')
                            ->inline(false)
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.last_name')
                    ->label('Employé')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('period')
                    ->label('Période')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gross_salary')
                    ->label('Brut')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_paid')
                    ->label('Net Payé')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (PayslipStatus $state): string => match ($state) {
                        PayslipStatus::DRAFT => 'gray',
                        PayslipStatus::VALIDATED => 'info',
                        PayslipStatus::PAID => 'success',
                    }),
                Tables\Columns\TextColumn::make('digiposte_status')
                    ->label('Digiposte')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'deposited' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('dsn_status')
                    ->label('DSN')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'ready' => 'info',
                        'exported' => 'warning',
                        'submitted' => 'primary',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('generate_pdf')
                        ->label('Générer PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function (Payslip $record) {
                            $service = app(PayslipPdfService::class);
                            $service->generatePdf($record);

                            Notification::make()
                                ->title('PDF généré avec succès')
                                ->success()
                                ->send();
                        }),
                    Action::make('download_pdf')
                        ->label('Télécharger PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn (Payslip $record) => $record->pdf_path ? Storage::disk('public')->url($record->pdf_path) : null)
                        ->openUrlInNewTab()
                        ->visible(fn (Payslip $record) => $record->pdf_path !== null),
                    Action::make('lock')
                        ->label('Clôturer')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Clôturer le bulletin')
                        ->modalDescription('Êtes-vous sûr de vouloir clôturer ce bulletin ? Cette action est irréversible et figera les éléments de paie associés (pointages, acomptes). Un PDF définitif sera généré.')
                        ->modalSubmitActionLabel('Oui, clôturer')
                        ->visible(fn (Payslip $record) => $record->status === PayslipStatus::DRAFT)
                        ->action(function (Payslip $record, PayslipLockService $lockService) {
                            $lockService->lock($record);
                            Notification::make()
                                ->title('Bulletin clôturé avec succès')
                                ->success()
                                ->send();
                        }),
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('lockSelection')
                        ->label('Clôturer la sélection')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Clôturer les bulletins sélectionnés')
                        ->modalDescription('Êtes-vous sûr de vouloir clôturer ces bulletins ? Cette action est irréversible et figera les éléments de paie associés (pointages, acomptes).')
                        ->modalSubmitActionLabel('Oui, clôturer')
                        ->action(function (Collection $records, PayslipLockService $lockService) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === PayslipStatus::DRAFT) {
                                    $lockService->lock($record);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("{$count} bulletin(s) clôturé(s)")
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('distribute')
                        ->label('Publier & Notifier')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Distribuer les bulletins de paie')
                        ->modalDescription('Cela publiera les bulletins sur les espaces salariés et enverra un e-mail et une notification aux employés concernés.')
                        ->modalSubmitActionLabel('Distribuer')
                        ->action(function (Collection $records) {
                            $validRecords = $records->filter(fn ($r) => in_array($r->status, [PayslipStatus::VALIDATED, PayslipStatus::PAID]));

                            if ($validRecords->isEmpty()) {
                                Notification::make()
                                    ->title('Aucun bulletin valide')
                                    ->body('Seuls les bulletins validés ou payés peuvent être distribués.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $count = 0;
                            foreach ($validRecords as $payslip) {
                                DistributePayslipJob::dispatch($payslip);
                                $count++;
                            }

                            Notification::make()
                                ->title("Distribution lancée pour $count bulletin(s)")
                                ->body('Les notifications et e-mails sont en cours d\'envoi en arrière-plan.')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('distributeDigiposte')
                        ->label('Distribuer via Digiposte')
                        ->icon('heroicon-o-archive-box-arrow-down')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Dépôt dans les coffres Digiposte')
                        ->modalDescription('Cela enverra les bulletins scellés directement dans le coffre-fort numérique de chaque salarié.')
                        ->modalSubmitActionLabel('Envoyer vers Digiposte')
                        ->action(function (Collection $records) {
                            $validRecords = $records->filter(fn ($r) => in_array($r->status, [PayslipStatus::VALIDATED, PayslipStatus::PAID]));

                            if ($validRecords->isEmpty()) {
                                Notification::make()
                                    ->title('Aucun bulletin valide')
                                    ->body('Seuls les bulletins validés ou payés peuvent être déposés.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $count = 0;
                            foreach ($validRecords as $payslip) {
                                SendPayslipToDigiposteJob::dispatch($payslip);
                                $count++;
                            }

                            Notification::make()
                                ->title("Dépôt Digiposte lancé pour $count bulletin(s)")
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('exportOd')
                        ->label('Exporter OD Comptable (CSV)')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $validRecords = $records->filter(fn ($r) => in_array($r->status, [PayslipStatus::VALIDATED, PayslipStatus::PAID]));

                            if ($validRecords->isEmpty()) {
                                Notification::make()
                                    ->title('Aucun bulletin valide')
                                    ->body('L\'export OD ne peut être généré que pour des bulletins validés ou payés.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $service = new AccountingExportService;
                            $path = $service->generateCsv($validRecords);

                            return response()->download(storage_path('app/public/'.$path));
                        }),
                    BulkAction::make('exportDsn')
                        ->label('Exporter DADS/DSN (CSV)')
                        ->icon('heroicon-o-table-cells')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Export DSN pour expert-comptable')
                        ->modalDescription('Génère le fichier CSV et enregistre la soumission DSN. Le fichier sera prêt au téléchargement.')
                        ->modalSubmitActionLabel('Générer l\'export')
                        ->action(function (Collection $records) {
                            $validRecords = $records->filter(fn ($r) => in_array($r->status, [PayslipStatus::VALIDATED, PayslipStatus::PAID]));

                            if ($validRecords->isEmpty()) {
                                Notification::make()
                                    ->title('Aucun bulletin valide')
                                    ->body('L\'export DSN ne peut être généré que pour des bulletins validés ou payés.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $period = $validRecords->first()->period;
                            $companyId = $validRecords->first()->employee->company_id ?? 1;

                            $service = new DsnExportService;
                            $submission = $service->generateForAccountant($validRecords, $period, $companyId, auth()->id());

                            auth()->user()->notify(new DsnExportedNotification($submission));

                            return Storage::disk('local')->download($submission->exported_file_path);
                        }),
                    BulkAction::make('markDsnReady')
                        ->label('Marquer DSN prête')
                        ->icon('heroicon-o-check-circle')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Marquer les bulletins comme prêts pour la DSN')
                        ->modalDescription('Cela marquera les bulletins sélectionnés comme prêts pour l\'export DSN.')
                        ->modalSubmitActionLabel('Marquer comme prêts')
                        ->action(function (Collection $records) {
                            $validRecords = $records->filter(fn ($r) => in_array($r->status, [PayslipStatus::VALIDATED, PayslipStatus::PAID]));

                            if ($validRecords->isEmpty()) {
                                Notification::make()
                                    ->title('Aucun bulletin valide')
                                    ->body('Seuls les bulletins validés ou payés peuvent être marqués comme prêts.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $service = new DsnExportService;
                            $service->markAsReady($validRecords);

                            Notification::make()
                                ->title("{$validRecords->count()} bulletin(s) marqué(s) comme prêts pour la DSN")
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('generateSepa')
                        ->label('Générer fichier SEPA')
                        ->icon('heroicon-o-currency-euro')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Export Virement SEPA')
                        ->modalDescription('Cela va générer le fichier XML SEPA pour les bulletins clôturés sélectionnés ayant un net à payer supérieur à zéro.')
                        ->modalSubmitActionLabel('Générer et télécharger')
                        ->action(function (Collection $records, SepaExportService $sepaService) {
                            try {
                                $validRecords = $records->filter(fn ($r) => $r->status === PayslipStatus::VALIDATED && $r->net_paid > 0);

                                if ($validRecords->isEmpty()) {
                                    Notification::make()
                                        ->title('Aucun bulletin valide')
                                        ->body('Sélectionnez des bulletins clôturés avec un net à payer supérieur à 0.')
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                $xml = $sepaService->generateXml($validRecords);
                                $filename = 'virements_salaires_'.date('Ymd_His').'.xml';

                                return response()->streamDownload(function () use ($xml) {
                                    echo $xml;
                                }, $filename, ['Content-Type' => 'application/xml']);
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur lors de l\'export SEPA')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    BulkAction::make('payByBridge')
                        ->label('Payer par virement API')
                        ->icon('heroicon-o-banknotes')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Paiement par API bancaire (Bridge)')
                        ->modalDescription('Cette action regroupe les bulletins clôturés sélectionnés en un run de paiement, initie la requête auprès de Bridge puis affiche le lien de validation bancaire.')
                        ->modalSubmitActionLabel('Créer le run de paiement')
                        ->form([
                            Forms\Components\Select::make('source_bank_account_id')
                                ->label('Compte émetteur (banque connectée)')
                                ->options(fn () => BankAccount::query()->whereNotNull('bridge_bank_id')->where('currency', 'EUR')->get()->mapWithKeys(fn ($account) => [
                                    $account->id => $account->name.' — Solde : '.Number::currency((float) $account->balance, 'EUR', 'fr'),
                                ]))
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data, SalaryPaymentService $salaryService) {
                            $source = BankAccount::find($data['source_bank_account_id']);

                            $validRecords = $records->filter(fn ($r) => $r->status === PayslipStatus::VALIDATED && $r->net_paid > 0);

                            if ($validRecords->isEmpty() || ! $source) {
                                Notification::make()
                                    ->title('Aucun bulletin payable')
                                    ->body('Sélectionnez des bulletins clôturés avec un net à payer supérieur à 0 et un compte émetteur.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            try {
                                $run = $salaryService->createRun($validRecords, $source, auth()->user());

                                if ($run->wasRecentlyCreated) {
                                    InitiateSalaryPaymentRunJob::dispatch($run);

                                    Notification::make()
                                        ->title('Run de paiement créé')
                                        ->body('L\'initiation est en cours. Ouvrez la validation bancaire depuis le suivi des runs de paiement.')
                                        ->success()
                                        ->actions([
                                            Action::make('view')
                                                ->label('Suivre le run')
                                                ->url(SalaryPaymentRunResource::getUrl('index')),
                                        ])
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Un run de paiement existe déjà pour ce lot')
                                        ->body('Aucune nouvelle initiation n\'a été lancée.')
                                        ->warning()
                                        ->actions([
                                            Action::make('view')
                                                ->label('Suivre le run')
                                                ->url(SalaryPaymentRunResource::getUrl('index')),
                                        ])
                                        ->send();
                                }
                            } catch (\Throwable $e) {
                                Notification::make()
                                    ->title('Erreur lors de la création du run')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identification')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.full_name')
                            ->label('Nom & Prénom'),

                        TextEntry::make('employee.full_address')
                            ->label('Adresse'),

                        TextEntry::make('employee.registration_number')
                            ->label('Matricule'),

                        TextEntry::make('employee.social_security_number')
                            ->label('Numéro de SS'),
                    ]),

                Section::make('Ressource Humaine')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('employee.currentContract.type')
                            ->label('Contrat')
                            ->badge(),

                        TextEntry::make('employee.currentContract.job_title')
                            ->label('Emploie'),

                        TextEntry::make('employee.currentContract.start_date')
                            ->label('Date d\'entrée')
                            ->date('d/m/Y')
                            ->helperText(fn (Model $record) => round($record->employee->currentContract->start_date->diffInMonth(Carbon::parse($record->period.'-01')->endOfMonth())).' mois'),

                        TextEntry::make('status')->label('Statut')
                            ->label('Statut')
                            ->badge()
                            ->color(fn (PayslipStatus $state): string => match ($state) {
                                PayslipStatus::DRAFT => 'gray',
                                PayslipStatus::VALIDATED => 'info',
                                PayslipStatus::PAID => 'success',
                            }),
                    ]),

                Section::make('Synthèse')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('gross_salary')->label('Salaire Brut')->money('EUR')->size('lg')->weight(FontWeight::Bold),
                        TextEntry::make('net_social')->label('Net Social')->money('EUR'),
                        TextEntry::make('net_payable')->label('Net à Payer (avant impôt)')->money('EUR'),
                        TextEntry::make('net_paid')->label('Net Payé')->money('EUR')->size('lg')->weight(FontWeight::Bold)->color('success'),
                    ]),

                Section::make('Données RH (Variables du mois)')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('base_hours')->label('Heures de base')->suffix(' h'),
                        TextEntry::make('overtime_hours')->label('Heures Supplémentaires')->suffix(' h'),
                        TextEntry::make('gd_allowance_amount')->label('Indemnités Gd Déplacement')->money('EUR'),
                        TextEntry::make('expense_reports_amount')->label('Remboursement Frais')->money('EUR'),
                    ]),

                Section::make('Absences, Primes et Indemnités')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('custom_bonuses')
                            ->label('')
                            ->schema([
                                TextEntry::make('label')
                                    ->label('Libellé')
                                    ->weight(FontWeight::SemiBold),
                                TextEntry::make('amount')->label('Montant')
                                    ->label('Montant')
                                    ->money('EUR')
                                    ->color(fn ($state) => $state < 0 ? 'danger' : 'success'),
                                TextEntry::make('is_taxable')
                                    ->label('Soumis à cotisations (Brut)')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Oui' : 'Non')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray'),
                            ])
                            ->columns(3),
                    ])
                    ->visible(fn (Model $record) => ! empty($record->custom_bonuses)),

                Section::make('Détail des cotisations')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->label('')
                            ->schema([
                                TextEntry::make('category')->label('Catégorie')->hiddenLabel(),
                                TextEntry::make('label')->label('Libellé')->hiddenLabel(),
                                TextEntry::make('base')->label('Base')->money('EUR')->hiddenLabel(),
                                TextEntry::make('employee_rate')->label('Taux Salarial')->suffix('%')->hiddenLabel(),
                                TextEntry::make('employee_amount')->label('Montant Sal.')->money('EUR')->hiddenLabel(),
                                TextEntry::make('employer_rate')->label('Taux Patronal')->suffix('%')->hiddenLabel(),
                                TextEntry::make('employer_amount')->label('Montant Pat.')->money('EUR')->hiddenLabel(),
                            ])
                            ->columns(7),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return $record->status === PayslipStatus::DRAFT;
    }

    public static function canDelete(Model $record): bool
    {
        return $record->status === PayslipStatus::DRAFT;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayslips::route('/'),
            'create' => CreatePayslip::route('/create'),
            'view' => ViewPayslip::route('/{record}'),
            'edit' => EditPayslip::route('/{record}/edit'),
        ];
    }
}
