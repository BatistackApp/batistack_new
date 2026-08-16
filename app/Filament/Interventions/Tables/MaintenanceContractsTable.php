<?php

namespace App\Filament\Interventions\Tables;

use App\Enums\Interventions\MaintenanceContractFrequency;
use App\Enums\Interventions\MaintenanceContractStatus;
use App\Models\Interventions\MaintenanceContract;
use App\Services\Interventions\MaintenanceContractService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaintenanceContractsTable
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

                TextColumn::make('name')
                    ->label('Nom du contrat')
                    ->searchable()
                    ->sortable()
                    ->description(fn (MaintenanceContract $record) => $record->clientEquipment?->name ?: $record->description),

                TextColumn::make('thirdParty.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('frequency')
                    ->label('Fréquence')
                    ->badge()
                    ->sortable(),

                TextColumn::make('next_due_date')
                    ->label('Prochaine échéance')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'gray'),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),

                TextColumn::make('flat_rate_price')
                    ->label('Prix HT')
                    ->money('EUR')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(MaintenanceContractStatus::class),
                SelectFilter::make('frequency')
                    ->label('Fréquence')
                    ->options(MaintenanceContractFrequency::class),
                SelectFilter::make('third_party_id')
                    ->label('Client')
                    ->relationship('thirdParty', 'name')
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('generate_now')
                    ->label('Générer maintenant')
                    ->icon('heroicon-o-bolt')
                    ->color('warning')
                    ->authorize('update')
                    ->visible(fn (MaintenanceContract $record) => $record->status === MaintenanceContractStatus::ACTIVE)
                    ->requiresConfirmation()
                    ->action(function (MaintenanceContract $record, MaintenanceContractService $service) {
                        try {
                            $created = $service->generateForContract($record, force: true);

                            Notification::make()
                                ->title($created ? 'Intervention générée !' : 'Aucune intervention générée')
                                ->body($created ? "L'intervention de maintenance pour « {$record->name} » a été créée." : 'Le contrat n\'a pas pu générer d\'intervention.')
                                ->{$created ? 'success' : 'warning'}()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('toggle_status')
                    ->label(fn (MaintenanceContract $record) => $record->status === MaintenanceContractStatus::ACTIVE ? 'Pause' : 'Reprendre')
                    ->icon(fn (MaintenanceContract $record) => $record->status === MaintenanceContractStatus::ACTIVE ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn (MaintenanceContract $record) => $record->status === MaintenanceContractStatus::ACTIVE ? 'warning' : 'success')
                    ->authorize('update')
                    ->visible(fn (MaintenanceContract $record) => in_array($record->status, [MaintenanceContractStatus::ACTIVE, MaintenanceContractStatus::PAUSED]))
                    ->action(function (MaintenanceContract $record) {
                        $record->update([
                            'status' => $record->status === MaintenanceContractStatus::ACTIVE
                                ? MaintenanceContractStatus::PAUSED
                                : MaintenanceContractStatus::ACTIVE,
                        ]);
                    }),
                DeleteAction::make()
                    ->label('Supprimer le contrat'),
            ]);
    }
}
