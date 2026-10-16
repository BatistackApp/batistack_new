<?php

namespace App\Filament\Commerce\Resources\CustomerSituations\Tables;

use App\Enums\Commerce\InvoiceStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class CustomerSituationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label('N°')->searchable()->sortable(),
                TextColumn::make('order.reference')->label('Commande')->searchable(),
                TextColumn::make('chantier.reference')->label('Chantier')->searchable(),
                TextColumn::make('status')->label('Statut')->badge()->sortable(),
                TextColumn::make('periode_start')->label('Début')->date('d/m/Y')->sortable(),
                TextColumn::make('periode_end')->label('Fin')->date('d/m/Y')->sortable(),
                TextColumn::make('total_ht')->label('Total HT')->numeric()->sortable(),
                TextColumn::make('total_ttc')->label('Total TTC')->numeric()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')
                    ->label('Statut')
                    ->options(InvoiceStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            if (! $records->every(fn ($r) => $r->canBeDeleted())) {
                                Notification::make()
                                    ->danger()
                                    ->title('Suppression impossible')
                                    ->body('Seuls les documents en brouillon peuvent être supprimés.')
                                    ->send();

                                return;
                            }

                            $records->each->delete();

                            Notification::make()
                                ->success()
                                ->title('Situations supprimées')
                                ->send();
                        }),
                ]),
            ]);
    }
}
