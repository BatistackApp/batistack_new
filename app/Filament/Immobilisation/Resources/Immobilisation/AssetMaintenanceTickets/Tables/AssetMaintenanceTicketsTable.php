<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenanceTickets\Tables;

use App\Enums\Immobilisation\AssetMaintenanceTicketStatus;
use App\Enums\Immobilisation\TicketSeverity;
use App\Models\Immobilisation\AssetMaintenanceTicket;
use App\Services\Immobilisation\AssetMaintenanceTicketService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssetMaintenanceTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('asset.name')
                    ->label('Actif')
                    ->state(fn (AssetMaintenanceTicket $record) => $record->asset?->name ?? '—')
                    ->description(fn (AssetMaintenanceTicket $record) => $record->asset ? class_basename($record->asset_type) : '')
                    ->searchable(query: fn ($query, string $search) => $query
                        ->whereHasMorph('asset', ['App\Models\Immobilisation\FixedAsset', 'App\Models\RH\Equipement'], fn ($q, string $type) => $type === 'App\Models\RH\Equipement'
                            ? $q->where('label', 'like', "%{$search}%")
                            : $q->where('name', 'like', "%{$search}%"))),
                TextColumn::make('chantier.name')
                    ->label('Chantier')
                    ->placeholder('—'),
                TextColumn::make('severity')
                    ->label('Gravité')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),
                TextColumn::make('reportedBy.full_name')
                    ->label('Déclaré par')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(AssetMaintenanceTicketStatus::class),
                SelectFilter::make('severity')
                    ->label('Gravité')
                    ->options(TicketSeverity::class),
                SelectFilter::make('asset_type')
                    ->label('Type d\'actif')
                    ->options([
                        'App\Models\Immobilisation\FixedAsset' => 'Immobilisation',
                        'App\Models\RH\Equipement' => 'Équipement RH',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    Action::make('start')
                        ->label('Prendre en charge')
                        ->icon('heroicon-o-play')
                        ->color('primary')
                        ->visible(fn (AssetMaintenanceTicket $record) => $record->status === AssetMaintenanceTicketStatus::OPEN)
                        ->action(function (AssetMaintenanceTicket $record) {
                            app(AssetMaintenanceTicketService::class)->start($record);

                            Notification::make()
                                ->title('Ticket pris en charge')
                                ->body($record->reference)
                                ->success()
                                ->send();
                        }),
                    Action::make('resolve')
                        ->label('Résoudre')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (AssetMaintenanceTicket $record) => in_array($record->status, [
                            AssetMaintenanceTicketStatus::OPEN,
                            AssetMaintenanceTicketStatus::IN_PROGRESS,
                        ], true))
                        ->schema([
                            TextInput::make('cost_ht')
                                ->label('Coût HT')
                                ->numeric()
                                ->prefix('€')
                                ->nullable(),
                            TextInput::make('provider_name')
                                ->label('Prestataire')
                                ->nullable(),
                        ])
                        ->action(function (AssetMaintenanceTicket $record, array $data) {
                            app(AssetMaintenanceTicketService::class)->resolve(
                                $record,
                                $data['cost_ht'] ?? null,
                                $data['provider_name'] ?? null,
                            );

                            Notification::make()
                                ->title('Ticket résolu')
                                ->body($record->reference.' — enregistrement de maintenance créé.')
                                ->success()
                                ->send();
                        }),
                    Action::make('cancel')
                        ->label('Annuler le ticket')
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->visible(fn (AssetMaintenanceTicket $record) => in_array($record->status, [
                            AssetMaintenanceTicketStatus::OPEN,
                            AssetMaintenanceTicketStatus::IN_PROGRESS,
                        ], true))
                        ->action(function (AssetMaintenanceTicket $record) {
                            app(AssetMaintenanceTicketService::class)->cancel($record);

                            Notification::make()
                                ->title('Ticket annulé')
                                ->body($record->reference)
                                ->warning()
                                ->send();
                        }),
                ]),
            ]);
    }
}
