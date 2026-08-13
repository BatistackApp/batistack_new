<?php

namespace App\Filament\RH\Resources\TrainingSessions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use App\Enums\RH\TrainingSessionStatus;
use App\Enums\RH\OpcoStatus;
use App\Services\RH\TrainingSessionService;
use App\Models\RH\TrainingSession;
use Filament\Notifications\Notification;

class TrainingSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Formation'),
                TextColumn::make('started_at')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Début'),
                TextColumn::make('ended_at')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Fin'),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->label('Statut'),
                TextColumn::make('cost')
                    ->money('eur')
                    ->sortable()
                    ->label('Coût'),
                TextColumn::make('opco_status')
                    ->badge()
                    ->sortable()
                    ->label('OPCO'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(TrainingSessionStatus::class),
                SelectFilter::make('opco_status')
                    ->options(OpcoStatus::class),
            ])
            ->actions([
                EditAction::make(),
                Action::make('complete_session')
                    ->label('Clôturer la session')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Clôturer la session de formation')
                    ->modalDescription('Êtes-vous sûr ? Cela renouvellera automatiquement les qualifications des participants validés.')
                    ->action(function (TrainingSession $record, TrainingSessionService $service) {
                        try {
                            $service->completeSession($record);
                            Notification::make()
                                ->title('Session clôturée avec succès')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (TrainingSession $record) => $record->status !== TrainingSessionStatus::TERMINEE),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
