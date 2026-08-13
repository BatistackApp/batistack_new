<?php

namespace App\Filament\RH\Resources\TimeEntries\Tables;

use App\Enums\RH\TimeEntryStatus;
use App\Models\RH\TimeEntry;
use App\Services\RH\TimeEntryService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class TimeEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->label('Date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('employee.full_name')
                    ->label('Salarié')
                    ->searchable(),
                TextColumn::make('hours')
                    ->label('Durée')
                    ->suffix(' h')
                    ->weight('bold'),
                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge(),
                IconColumn::make('is_grand_deplacement')
                    ->label('GD')
                    ->boolean()
                    ->tooltip(fn ($record) => $record->is_grand_deplacement ? 'Indemnité 96€ appliquée' : null),
                IconColumn::make('is_anomaly')
                    ->label('Anomalie')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->trueColor('danger')
                    ->falseIcon('')
                    ->tooltip(fn ($record) => $record->anomaly_reason),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')
                    ->options(TimeEntryStatus::class),
                Filter::make('to_validate')
                    ->label('À valider uniquement')
                    ->query(fn ($query) => $query->where('status', TimeEntryStatus::SUBMITTED)),
                Filter::make('anomalies')
                    ->label('Anomalies non résolues')
                    ->query(fn ($query) => $query->where('is_anomaly', true)->whereNull('anomaly_resolved_at')),
            ])
            ->recordActions([
                ActionGroup::make([
                    // ACTION APPROUVER
                    Action::make('approve')
                        ->label('Approuver')
                        ->icon(Phosphor::CheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->status === TimeEntryStatus::SUBMITTED)
                        ->action(function (TimeEntry $record, TimeEntryService $service) {
                            $service->approve($record);
                            Notification::make()->title('Pointage approuvé')->success()->send();
                        }),

                    // ACTION REFUSER
                    Action::make('refuse')
                        ->label('Refuser')
                        ->icon(Phosphor::XCircle)
                        ->color('danger')
                        ->visible(fn ($record) => $record->status === TimeEntryStatus::SUBMITTED)
                        ->schema([
                            Textarea::make('reason')
                                ->label('Motif du refus')
                                ->required()
                                ->placeholder('ex: Erreur sur le chantier sélectionné'),
                        ])
                        ->action(function (TimeEntry $record, array $data, TimeEntryService $service) {
                            $service->refuse($record, $data['reason']);
                            Notification::make()->title('Pointage renvoyé pour correction')->warning()->send();
                        }),

                    // ACTION RÉSOUDRE ANOMALIE
                    Action::make('resolve_anomaly')
                        ->label('Résoudre l\'anomalie')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->is_anomaly && !$record->anomaly_resolved_at)
                        ->action(function (TimeEntry $record) {
                            $record->update([
                                'anomaly_resolved_at' => now(),
                                'anomaly_resolved_by_id' => auth()->id(),
                            ]);
                            Notification::make()->title('Anomalie résolue')->success()->send();
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    Action::make('submit')
                        ->accessSelectedRecords()
                        ->label('Changer le status')
                        ->schema([
                            Select::make('status')->label('Statut')
                                ->label('Statut')
                                ->options(TimeEntryStatus::class),
                        ])
                        ->action(function (array $data, EloquentCollection|Collection|LazyCollection $records) {
                            $records->each(function (TimeEntry $record) use ($data) {
                                $record->status = $data['status'];
                                $record->save();
                            });
                        }),
                ]),
            ]);
    }
}
