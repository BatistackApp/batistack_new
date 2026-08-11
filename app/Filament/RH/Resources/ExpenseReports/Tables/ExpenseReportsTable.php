<?php

namespace App\Filament\RH\Resources\ExpenseReports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExpenseReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.last_name')
                    ->label('Employé')
                    ->formatStateUsing(fn ($record) => "{$record->employee->first_name} {$record->employee->last_name}")
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('month')
                    ->label('Mois')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('year')
                    ->label('Année')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (\App\Enums\RH\ExpenseReportStatus $state): string => $state->getColor())
                    ->formatStateUsing(fn (\App\Enums\RH\ExpenseReportStatus $state): string => $state->getLabel())
                    ->searchable(),
                TextColumn::make('total_amount')->label('Montant total')
                    ->label('Montant Total')
                    ->numeric()
                    ->suffix(' €')
                    ->sortable(),
                TextColumn::make('created_at')->label('Créé le')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Mis à jour le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\BulkAction::make('export_sepa')
                        ->label('Générer virements SEPA')
                        ->icon('heroicon-o-currency-euro')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Exporter au format SEPA')
                        ->modalDescription('Cette action va générer un fichier XML de virement SEPA pour les notes de frais sélectionnées.')
                        ->form([
                            \Filament\Forms\Components\Checkbox::make('mark_as_paid')
                                ->label('Marquer les notes de frais comme payées ?')
                                ->default(true)
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data, \App\Services\RH\SepaExportService $service) {
                            // Ne traiter que les notes validées
                            $validatedRecords = $records->filter(fn ($r) => $r->status === \App\Enums\RH\ExpenseReportStatus::VALIDATED);
                            
                            if ($validatedRecords->isEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Aucune note de frais validée sélectionnée.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            try {
                                $xmlContent = $service->generateForExpenseReports($validatedRecords);
                                
                                if ($data['mark_as_paid']) {
                                    foreach ($validatedRecords as $record) {
                                        $record->update(['status' => \App\Enums\RH\ExpenseReportStatus::PAID]);
                                    }
                                }
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Fichier SEPA généré avec succès.')
                                    ->success()
                                    ->send();
                                
                                return response()->streamDownload(function () use ($xmlContent) {
                                    echo $xmlContent;
                                }, 'virements_sepa_' . date('Ymd_His') . '.xml');
                                
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Erreur lors de la génération SEPA')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
