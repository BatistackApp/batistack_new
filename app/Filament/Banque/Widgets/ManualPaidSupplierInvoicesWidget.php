<?php

namespace App\Filament\Banque\Widgets;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\SupplierInvoice;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ManualPaidSupplierInvoicesWidget extends TableWidget
{
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Factures Fournisseurs "Payées" sans paiement bancaire (anomalie)';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => SupplierInvoice::query()
                ->where('status', InvoiceStatus::PAID)
                ->doesntHave('allocations')
                ->latest('due_date')
            )
            ->columns([
                TextColumn::make('reference')->label('Numéro')->searchable(),
                TextColumn::make('supplier.name')->label('Fournisseur')->searchable(),
                TextColumn::make('due_date')->label('Échéance')->date('d/m/Y')->sortable(),
                TextColumn::make('amount_ttc')->label('Montant TTC')->money('EUR')->sortable(),
            ])
            ->recordActions([
                Action::make('voir')
                    ->label('Consulter')
                    ->icon('heroicon-o-eye')
                    ->url(fn (SupplierInvoice $record) => \App\Filament\Commerce\Resources\SupplierInvoices\SupplierInvoiceResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
