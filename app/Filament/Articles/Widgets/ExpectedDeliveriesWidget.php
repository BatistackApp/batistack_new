<?php

namespace App\Filament\Articles\Widgets;

use App\Enums\Commerce\OrderStatus;
use App\Models\Commerce\PurchaseOrder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ExpectedDeliveriesWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Livraisons Attendues (Commandes)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PurchaseOrder::query()
                    ->with('supplier')
                    ->where('status', OrderStatus::CONFIRMED)
                    ->whereNotNull('expected_delivery_date')
                    ->orderBy('expected_delivery_date', 'asc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('expected_delivery_date')
                    ->label('Date Prévue')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($state) => $state->isPast() ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('reference')->label('Référence')
                    ->label('Référence')
                    ->searchable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Fournisseur'),
                Tables\Columns\TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge()
                    ->colors([
                        'warning' => OrderStatus::CONFIRMED,
                    ])
                    ->formatStateUsing(fn ($state) => $state->name),
                Tables\Columns\TextColumn::make('total_ht')
                    ->label('Montant HT')
                    ->money('EUR'),
            ])
            ->paginated(false);
    }
}
