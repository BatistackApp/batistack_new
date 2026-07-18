<?php

namespace App\Filament\Paie\Resources\Paie\Payslips;

use App\Enums\Paie\PayslipStatus;
use App\Filament\Paie\Resources\Paie\Payslips\Pages\CreatePayslip;
use App\Filament\Paie\Resources\Paie\Payslips\Pages\EditPayslip;
use App\Filament\Paie\Resources\Paie\Payslips\Pages\ListPayslips;
use App\Filament\Paie\Resources\Paie\Payslips\Pages\ViewPayslip;
use App\Jobs\Paie\DistributePayslipJob;
use App\Models\Paie\Payslip;
use App\Models\RH\Employee;
use App\Services\Paie\PayslipLockService;
use App\Services\Paie\PayslipPdfService;
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
                Forms\Components\Select::make('status')
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
                        Forms\Components\TextInput::make('amount')
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
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (PayslipStatus $state): string => match ($state) {
                        PayslipStatus::DRAFT => 'gray',
                        PayslipStatus::VALIDATED => 'info',
                        PayslipStatus::PAID => 'success',
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

                        TextEntry::make('status')
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
