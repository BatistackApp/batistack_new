<?php

namespace App\Filament\Paie\Resources\Paie\Payslips;

use App\Enums\Paie\PayslipStatus;
use App\Filament\Paie\Resources\Paie\Payslips\Pages\CreatePayslip;
use App\Filament\Paie\Resources\Paie\Payslips\Pages\EditPayslip;
use App\Filament\Paie\Resources\Paie\Payslips\Pages\ListPayslips;
use App\Filament\Paie\Resources\Paie\Payslips\Pages\ViewPayslip;
use App\Models\Paie\Payslip;
use App\Models\RH\Employee;
use App\Services\Paie\PayslipPdfService;
use BackedEnum;
use Filament\Actions\Action;
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
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
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
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations générales')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('employee.full_name')->label('Employé'),
                        TextEntry::make('period')->label('Période'),
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
