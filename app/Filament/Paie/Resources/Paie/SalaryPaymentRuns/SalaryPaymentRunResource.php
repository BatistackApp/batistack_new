<?php

namespace App\Filament\Paie\Resources\Paie\SalaryPaymentRuns;

use App\Enums\Paie\SalaryPaymentStatus;
use App\Filament\Paie\Resources\Paie\SalaryPaymentRuns\Pages\ListSalaryPaymentRuns;
use App\Models\Paie\SalaryPaymentRun;
use App\Services\Paie\SalaryPaymentService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

class SalaryPaymentRunResource extends Resource
{
    protected static ?string $model = SalaryPaymentRun::class;

    protected static ?string $modelLabel = 'Run de paiement';

    protected static ?string $pluralModelLabel = 'Runs de paiement';

    protected static string|null|\UnitEnum $navigationGroup = 'Gestion de la Paie';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Run')
                    ->sortable(),
                Tables\Columns\TextColumn::make('period')
                    ->label('Période')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('bankAccount.name')
                    ->label('Compte émetteur')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lines_count')
                    ->label('Lignes')
                    ->counts('lines')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (SalaryPaymentStatus $state): string => match ($state) {
                        SalaryPaymentStatus::PENDING => 'gray',
                        SalaryPaymentStatus::AWAITING_VALIDATION => 'warning',
                        SalaryPaymentStatus::PROCESSING => 'info',
                        SalaryPaymentStatus::SUCCEEDED => 'success',
                        SalaryPaymentStatus::FAILED => 'danger',
                        SalaryPaymentStatus::CANCELED => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('open_validation')
                        ->label('Ouvrir la validation bancaire')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->color('warning')
                        ->url(fn (SalaryPaymentRun $record) => $record->consent_url)
                        ->openUrlInNewTab()
                        ->visible(fn (SalaryPaymentRun $record) => $record->consent_url !== null && $record->status === SalaryPaymentStatus::AWAITING_VALIDATION),
                    Action::make('refresh')
                        ->label('Rafraîchir le statut')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function (SalaryPaymentRun $record, SalaryPaymentService $service) {
                            try {
                                $service->pollRun($record);
                                $record->refresh();

                                Notification::make()
                                    ->title('Statut mis à jour')
                                    ->body('Statut actuel : '.$record->status->getLabel())
                                    ->success()
                                    ->send();
                            } catch (\Throwable $e) {
                                Log::error("Échec du rafraîchissement du run {$record->id}: ".$e->getMessage());
                                Notification::make()
                                    ->title('Échec du rafraîchissement')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (SalaryPaymentRun $record) => $record->status !== SalaryPaymentStatus::SUCCEEDED && $record->status !== SalaryPaymentStatus::FAILED && $record->status !== SalaryPaymentStatus::CANCELED),
                    Action::make('reinitiate')
                        ->label('Relancer l\'initiation')
                        ->icon('heroicon-o-arrow-path')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Relancer l\'initiation du run')
                        ->modalDescription('Le lien de validation ayant probablement expiré, un nouveau lien sera généré auprès de Bridge.')
                        ->action(function (SalaryPaymentRun $record, SalaryPaymentService $service) {
                            try {
                                $service->reinitiateRun($record);
                                Notification::make()
                                    ->title('Initiation relancée')
                                    ->body('Un nouveau lien de validation a été généré.')
                                    ->success()
                                    ->send();
                            } catch (\Throwable $e) {
                                Log::error("Échec de la réinitiation du run {$record->id}: ".$e->getMessage());
                                Notification::make()
                                    ->title('Échec de la réinitiation')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (SalaryPaymentRun $record) => $record->status === SalaryPaymentStatus::AWAITING_VALIDATION),
                ]),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalaryPaymentRuns::route('/'),
        ];
    }
}
