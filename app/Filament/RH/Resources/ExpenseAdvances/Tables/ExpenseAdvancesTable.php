<?php

namespace App\Filament\RH\Resources\ExpenseAdvances\Tables;

use App\Enums\RH\ExpenseAdvanceStatus;
use App\Services\RH\SepaExportService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ExpenseAdvancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.last_name')
                    ->label('Employé')
                    ->formatStateUsing(fn ($record) => $record->employee ? "{$record->employee->first_name} {$record->employee->last_name}" : '-')
                    ->sortable()
                    ->searchable(['first_name', 'last_name']),

                TextColumn::make('amount')->label('Montant')
                    ->money('EUR')
                    ->sortable()
                    ->label('Montant'),

                TextColumn::make('request_date')
                    ->date()
                    ->sortable()
                    ->label('Date'),

                TextColumn::make('status')->label('Statut')
                    ->badge()
                    ->label('Statut'),

                TextColumn::make('expenseReport.id')
                    ->label('Note liée')
                    ->url(fn ($record) => $record->expense_report_id ? route('filament.rh.resources.expense-reports.edit', $record->expense_report_id) : null)
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')
                    ->options(ExpenseAdvanceStatus::class)
                    ->label('Statut'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('mark_paid')
                    ->label('Marquer Payée')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === ExpenseAdvanceStatus::APPROVED)
                    ->action(fn ($record) => $record->update(['status' => ExpenseAdvanceStatus::PAID, 'payment_date' => now()])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('export_sepa')
                        ->label('Exporter SEPA (Payer)')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records, SepaExportService $sepaService) {
                            // Filter only approved ones
                            $validRecords = $records->filter(fn ($r) => $r->status === ExpenseAdvanceStatus::APPROVED);
                            if ($validRecords->isEmpty()) {
                                Notification::make()->title('Aucune avance approuvée sélectionnée.')->danger()->send();

                                return;
                            }

                            $xml = $sepaService->generateForExpenseAdvances($validRecords);

                            // Mark as paid
                            foreach ($validRecords as $record) {
                                $record->update(['status' => ExpenseAdvanceStatus::PAID, 'payment_date' => now()]);
                            }

                            return response()->streamDownload(function () use ($xml) {
                                echo $xml;
                            }, 'avances_sepa_'.date('YmdHis').'.xml', ['Content-Type' => 'application/xml']);
                        }),
                ]),
            ]);
    }
}
