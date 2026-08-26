<?php

namespace App\Filament\Interventions\Tables;

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Filament\Interventions\InterventionResource;
use App\Services\Interventions\InterventionManagementService;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class InterventionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Référence')
                    ->label('Référence')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('thirdParty.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')->label('Type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),

                TextColumn::make('scheduled_at')
                    ->label('Date planifiée')
                    ->date('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('completed_at')
                    ->label('Date de clôture')
                    ->date('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')
                    ->label('Statut')
                    ->options(InterventionStatus::class),
                SelectFilter::make('type')->label('Type')
                    ->label('Type')
                    ->options(InterventionType::class),
                SelectFilter::make('third_party_id')
                    ->label('Client')
                    ->relationship('thirdParty', 'name')
                    ->searchable(),
            ])
            ->recordActions(array_merge([
                ViewAction::make(),
                EditAction::make(),
            ], InterventionResource::getSharedActions()))
            ->groupedBulkActions([
                DeleteBulkAction::make(),
                BulkAction::make('change_status')
                    ->label('Changer le statut')
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        Select::make('status')->label('Statut')
                            ->label('Nouveau statut')
                            ->options(InterventionStatus::class)
                            ->required(),
                    ])
                    ->action(function (Collection $records, array $data, InterventionManagementService $interventionService): void {
                        try {
                            DB::transaction(function () use ($records, $data, $interventionService) {
                                foreach ($records as $record) {
                                    Gate::authorize('update', $record);

                                    $targetStatus = $data['status'];
                                    $isTerminee = $targetStatus === InterventionStatus::TERMINEE->value || $targetStatus === InterventionStatus::TERMINEE;

                                    if ($isTerminee) {
                                        $success = $interventionService->completeIntervention($record);
                                    } else {
                                        $success = $record->update(['status' => $targetStatus]);
                                    }

                                    if (! $success) {
                                        throw new \Exception("La mise à jour a échoué pour l'intervention {$record->reference}");
                                    }
                                }
                            });

                            Notification::make()
                                ->title('Statuts mis à jour avec succès')
                                ->success()
                                ->send();
                        } catch (AuthorizationException $e) {
                            Notification::make()
                                ->title('Action non autorisée')
                                ->body('Vous n\'avez pas la permission de modifier cette intervention.')
                                ->danger()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}
