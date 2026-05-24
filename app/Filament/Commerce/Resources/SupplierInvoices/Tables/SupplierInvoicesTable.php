<?php

namespace App\Filament\Commerce\Resources\SupplierInvoices\Tables;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\SupplierInvoice;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
                TextColumn::make('reference')
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

                TextColumn::make('status')
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
                SelectFilter::make('status')
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
                ]),
            ]);
    }
}
