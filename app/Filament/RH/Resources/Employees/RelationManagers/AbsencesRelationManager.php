<?php

namespace App\Filament\RH\Resources\Employees\RelationManagers;

use App\Enums\RH\AbsenceType;
use App\Models\RH\Abscence;
use App\Services\RH\CibtpService;
use App\Services\RH\LeaveBalanceService;
use App\Services\RH\RHDocumentService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class AbsencesRelationManager extends RelationManager
{
    protected static string $relationship = 'absences';

    protected static ?string $title = 'Suivi des Absences & Congés';

    protected static string|BackedEnum|null $icon = Phosphor::CalendarBlank;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Saisie de l\'absence')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('type')
                            ->label('Nature de l\'absence')
                            ->options(AbsenceType::class)
                            ->required()
                            ->native(false),
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('start_date')
                                    ->label('Date de début')
                                    ->required()
                                    ->native(false),
                                DateTimePicker::make('end_date')
                                    ->label('Date de fin')
                                    ->required()
                                    ->native(false),
                            ]),
                        Textarea::make('reason')
                            ->label('Motif / Commentaire')
                            ->columnSpanFull(),
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_paid')
                                    ->label('Absence rémunérée')
                                    ->default(true),
                                Toggle::make('requires_subrogation')
                                    ->label('Subrogation (PRO BTP)')
                                    ->live(),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('ij_expected')
                                    ->label('IJ Attendues (€)')
                                    ->numeric()
                                    ->prefix('€'),
                                TextInput::make('ij_received')
                                    ->label('IJ Reçues (€)')
                                    ->numeric()
                                    ->prefix('€')
                                    ->default(0),
                                DatePicker::make('ij_payment_date')
                                    ->label('Date Paiement IJ')
                                    ->native(false),
                            ])
                            ->visible(fn (Get $get) => $get('requires_subrogation')),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('period')
                    ->label('Période')
                    ->getStateUsing(fn ($record) => "Du {$record->start_date->format('d/m/Y')} au {$record->end_date->format('d/m/Y')}"),
                TextColumn::make('duration')
                    ->label('Durée (Jours ouvrés)')
                    ->getStateUsing(function ($record) {
                        // Utilisation du service pour calculer les jours réels (excluant week-ends)
                        return app(LeaveBalanceService::class)->getConsumedDays($record->employee, $record->type);
                    })
                    ->suffix(' j'),
                IconColumn::make('is_paid')
                    ->label('Payé')
                    ->boolean(),

                TextColumn::make('cibtp_declared_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Déclaré le')
                    ->formatStateUsing(function (Abscence $record) {
                        return $record->cibtp_declared_at ? $record->cibtp_declared_at->format('d/m/Y') : 'Non Déclaré';
                    })
                    ->badge()
                    ->color(fn (Abscence $record) => $record->cibtp_declared_at ? 'success' : 'danger'),
            ])
            ->headerActions([
                CreateAction::make()->label('Déclarer une absence'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('start_declaration_cibtp')
                    ->label('Déclarer l\'absence')
                    ->icon(Phosphor::ShieldCheck)
                    ->schema([
                        DatePicker::make('cibtp_declared_at')
                            ->label('Date de la déclaration')
                            ->required(),
                    ])
                    ->action(function (Abscence $record, array $data) {
                        $record->cibtp_declared_at = $data['cibtp_declared_at'];
                        $record->save();

                        return redirect('https://mon-espace.cibtp.fr/24/adh/connexion');
                    })
                    ->visible(fn (Abscence $record) => ! $record->cibtp_declared_at),
                Action::make('download_attestation')
                    ->label('Attestation IJ')
                    ->icon(Phosphor::FilePdf)
                    ->color('info')
                    ->action(function (Abscence $record) {
                        $media = $record->getFirstMedia('attestations_salaire');
                        if ($media) {
                            return response()->download($media->getPath(), $media->file_name);
                        }

                        // Si le média n'existe pas encore, on le génère à la volée
                        try {
                            $pdfPath = app(RHDocumentService::class)->generateAttestationSalaire($record);
                            $media = $record->addMedia($pdfPath)->toMediaCollection('attestations_salaire');

                            return response()->download($media->getPath(), $media->file_name);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur lors de la génération')
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Abscence $record) => in_array($record->type, [AbsenceType::SICK_LEAVE, AbsenceType::WORK_ACCIDENT])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('exporter_ddc')
                        ->label('Exporter DDC (CIBTP)')
                        ->icon(Phosphor::FileCsv)
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Exporter les demandes de congés')
                        ->modalDescription('Cela va générer un fichier CSV contenant les demandes de congés sélectionnées et marquera ces absences comme "Déclarées". Êtes-vous sûr ?')
                        ->action(function (Collection $records) {
                            // Ne traiter que les congés payés
                            $paidLeaves = $records->where('type', AbsenceType::PAID_LEAVE);

                            if ($paidLeaves->isEmpty()) {
                                Notification::make()
                                    ->title('Aucun congé payé sélectionné')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            // Marquer comme déclaré
                            $paidLeaves->each(function ($absence) {
                                $absence->update(['cibtp_declared_at' => now()]);
                            });

                            $csvContent = app(CibtpService::class)->generateDDC($paidLeaves);

                            return response()->streamDownload(function () use ($csvContent) {
                                echo $csvContent;
                            }, 'DDC_CIBTP_'.now()->format('Ymd_His').'.csv', [
                                'Content-Type' => 'text/csv; charset=UTF-8',
                            ]);
                        }),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
