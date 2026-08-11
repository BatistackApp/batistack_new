<?php

namespace App\Filament\Gpao\ManufacturingOrders\Tables;

use App\Enums\Gpao\ManufacturingStatus;
use App\Jobs\Gpao\GeneratePurchaseOrdersForShortagesJob;
use App\Models\Gpao\ManufacturingOrder;
use App\Services\Gpao\GpaoDocumentService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ManufacturingOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Référence')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('item.name')
                    ->label('Article')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customerOrder.reference')
                    ->label('Cmd. Origine')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->customer_order_id ? route('filament.commerce.resources.customer-orders.edit', $record->customer_order_id) : null)
                    ->color('primary')
                    ->openUrlInNewTab(),

                TextColumn::make('quantity_planned')
                    ->label('Qte. Prévue')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('quantity_produced')
                    ->label('Qte. Produite')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),

                TextColumn::make('planned_start_date')
                    ->label('Début')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')
                    ->label('Statut')
                    ->options(ManufacturingStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('start')
                    ->label('Démarrer')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (ManufacturingOrder $record) => in_array($record->status, [ManufacturingStatus::DRAFT, ManufacturingStatus::PLANNED]))
                    ->action(fn (ManufacturingOrder $record) => $record->update(['status' => ManufacturingStatus::IN_PROGRESS])),

                Action::make('complete')
                    ->label('Terminer (Au contrôle)')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (ManufacturingOrder $record) => $record->status === ManufacturingStatus::IN_PROGRESS)
                    ->action(fn (ManufacturingOrder $record) => $record->update(['status' => ManufacturingStatus::QUALITY_CONTROL])),

                Action::make('quality_control')
                    ->label('Contrôle Qualité')
                    ->icon('heroicon-o-shield-check')
                    ->color('fuchsia')
                    ->visible(fn (ManufacturingOrder $record) => $record->status === ManufacturingStatus::QUALITY_CONTROL)
                    ->form([
                        Radio::make('status')->label('Statut')
                            ->label('Résultat')
                            ->options([
                                'passed' => 'Validé',
                                'failed' => 'Refusé',
                            ])
                            ->required(),
                        Textarea::make('notes')->label('Notes')
                            ->label('Notes / Commentaires'),
                    ])
                    ->action(function (array $data, ManufacturingOrder $record) {
                        $record->qualityChecks()->create([
                            'inspector_id' => auth()->id(),
                            'status' => $data['status'],
                            'notes' => $data['notes'],
                            'checked_at' => now(),
                        ]);

                        if ($data['status'] === 'passed') {
                            $record->update(['status' => ManufacturingStatus::COMPLETED]);
                            Notification::make()->title('Contrôle validé. Produit en stock.')->success()->send();
                        } else {
                            $record->update(['status' => ManufacturingStatus::IN_PROGRESS]);
                            Notification::make()->title('Contrôle refusé. OF renvoyé en cours.')->warning()->send();
                        }
                    }),

                Action::make('download_pdf')
                    ->label('Télécharger PDF (Étiquette)')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (ManufacturingOrder $record) {
                        // Check if media exists
                        $media = $record->getFirstMedia('pdf_documents');
                        if ($media) {
                            return response()->download($media->getPath(), $media->file_name);
                        }

                        // Generate on the fly
                        $pdfPath = (new GpaoDocumentService)->generateManufacturingOrderPdf($record);
                        $disk = \App\Services\Core\DocumentService::getDisk();
                        $media = $record->addMediaFromDisk($pdfPath, $disk)->toMediaCollection('pdf_documents');

                        return response()->download($media->getPath(), $media->file_name);
                    }),
            ])
            ->groupedBulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('calculate_shortages')
                    ->label('Calculer les ruptures (Achats)')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('warning')
                    ->action(function () {
                        GeneratePurchaseOrdersForShortagesJob::dispatch();
                        Notification::make()
                            ->title('Analyse MRP lancée')
                            ->body('La génération des brouillons de commande d\'achat est en cours.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
