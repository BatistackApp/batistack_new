<?php

namespace App\Filament\Commerce\Resources\SupplierInvoices\Tables;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\SupplierInvoice;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Référence')
                    ->label('Numéro')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('supplier.name')
                    ->label('Fournisseur')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('order.reference')
                    ->label('Bon de commande')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('amount_ttc')
                    ->label('Montant TTC')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')
                    ->options(InvoiceStatus::class),

                SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('has_dispute')
                    ->label('Factures en litige')
                    ->query(fn (Builder $query) => $query->where('status', InvoiceStatus::LITIGE)),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('pay')
                        ->label('Enregistrer paiement')
                        ->icon('heroicon-o-banknotes')
                        ->visible(fn (SupplierInvoice $record) => $record->status === InvoiceStatus::BON_A_PAYER)
                        ->url(fn (SupplierInvoice $record) => route('filament.commerce.resources.payments.create', ['supplier_invoice' => $record->id])),

                    Action::make('viewDispute')
                        ->label('Voir le litige')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->visible(fn (SupplierInvoice $record) => $record->status === InvoiceStatus::LITIGE)
                        ->schema([
                            Textarea::make('dispute_reason')
                                ->label('Raison du litige')
                                ->disabled()
                                ->default(fn (SupplierInvoice $record) => $record->dispute_reason),
                        ])
                        ->modalHeading('Détails du litige')
                        ->modalCancelActionLabel('Fermer'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('export_sepa')
                        ->label('Payer par virement (SEPA)')
                        ->icon('heroicon-o-currency-euro')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Exporter au format SEPA')
                        ->modalDescription('Cette action va générer un fichier XML de virement SEPA pour les factures sélectionnées.')
                        ->schema([
                            \Filament\Forms\Components\Checkbox::make('mark_as_paid')
                                ->label('Passer les factures en statut "Paiement en cours" ?')
                                ->default(true)
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data, \App\Services\Commerce\SepaExportService $service) {
                            $validRecords = $records->filter(fn ($r) => in_array($r->status, [InvoiceStatus::BON_A_PAYER, InvoiceStatus::VALIDATED]));

                            if ($validRecords->count() !== $records->count()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Sélection invalide')
                                    ->body('Certaines factures sélectionnées ne sont pas "Bon à payer" ou "Validée". L\'export a été annulé.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            if ($validRecords->isEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Aucune facture "Bon à payer" ou "Validée" sélectionnée.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            try {
                                $company = \App\Models\Core\Company::first();
                                $xmlContent = null;
                                
                                \Illuminate\Support\Facades\DB::transaction(function () use (&$xmlContent, $validRecords, $service, $data, $company) {
                                    $lockedRecords = \App\Models\Commerce\SupplierInvoice::whereIn('id', $validRecords->pluck('id'))
                                        ->lockForUpdate()
                                        ->get();
                                        
                                    $xmlContent = $service->generateForSupplierInvoices($lockedRecords, $company);

                                    if ($data['mark_as_paid']) {
                                        foreach ($lockedRecords as $record) {
                                            $record->update(['status' => InvoiceStatus::PAYMENT_IN_PROGRESS]);
                                        }
                                    }
                                });

                                \Filament\Notifications\Notification::make()
                                    ->title('Fichier SEPA généré avec succès.')
                                    ->success()
                                    ->send();

                                return response()->streamDownload(function () use ($xmlContent) {
                                    echo $xmlContent;
                                }, 'fournisseurs_sepa_' . date('Ymd_His') . '.xml', ['Content-Type' => 'application/xml']);

                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Erreur lors de la génération SEPA')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
