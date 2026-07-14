<?php

namespace App\Filament\Salarie\Resources\ExpenseReports\Tables;

use App\Enums\RH\ExpenseReportStatus;
use App\Models\RH\ExpenseReport;
use App\Services\RH\ExpenseWorkflowService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExpenseReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('month')
                    ->label('Mois')
                    ->formatStateUsing(fn ($state) => Carbon::create()->month($state)->translatedFormat('F'))
                    ->sortable(),
                TextColumn::make('year')
                    ->label('Année')
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Montant Total')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('submit')
                    ->label('Transférer')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Transférer la note de frais')
                    ->modalDescription('Êtes-vous sûr de vouloir soumettre cette note de frais à la validation ? Vous ne pourrez plus la modifier ensuite.')
                    ->modalSubmitActionLabel('Oui, transférer')
                    ->visible(fn (ExpenseReport $record) => $record->status === ExpenseReportStatus::DRAFT)
                    ->action(function (ExpenseReport $record) {
                        try {
                            app(ExpenseWorkflowService::class)->submit($record);
                            Notification::make()
                                ->success()
                                ->title('Note de frais transférée avec succès')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Erreur')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
