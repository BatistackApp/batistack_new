<?php

namespace App\Filament\Interventions\Tables;

use App\Enums\Core\SignatureType;
use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Interventions\Intervention;
use App\Services\Core\SignatureService;
use App\Services\Interventions\InterventionBillingService;
use App\Services\Interventions\InterventionPdfService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class InterventionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('thirdParty.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('status')
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
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(InterventionStatus::class),
                SelectFilter::make('type')
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
            ], \App\Filament\Interventions\InterventionResource::getSharedActions()))
            ->groupedBulkActions([
                DeleteBulkAction::make(),
                BulkAction::make('change_status')
                    ->label('Changer le statut')
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        \Filament\Forms\Components\Select::make('status')
                            ->label('Nouveau statut')
                            ->options(InterventionStatus::class)
                            ->required(),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data, \App\Services\Interventions\InterventionManagementService $interventionService): void {
                        try {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($records, $data, $interventionService) {
                                foreach ($records as $record) {
                                    \Illuminate\Support\Facades\Gate::authorize('update', $record);

                                    $targetStatus = $data['status'];
                                    $isTerminee = $targetStatus === InterventionStatus::TERMINEE->value || $targetStatus === InterventionStatus::TERMINEE;

                                    if ($isTerminee) {
                                        $success = $interventionService->completeIntervention($record);
                                    } else {
                                        $success = $record->update(['status' => $targetStatus]);
                                    }

                                    if (!$success) {
                                        throw new \Exception("La mise à jour a échoué pour l'intervention {$record->reference}");
                                    }
                                }
                            });

                            Notification::make()
                                ->title('Statuts mis à jour avec succès')
                                ->success()
                                ->send();
                        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
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
