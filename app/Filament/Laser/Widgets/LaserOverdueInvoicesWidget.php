<?php

namespace App\Filament\Laser\Widgets;

use App\Enums\Laser\InvoiceStatus;
use App\Models\Laser\LaserInvoice;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LaserOverdueInvoicesWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Factures en retard de paiement';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                LaserInvoice::query()
                    ->with('client')
                    ->where('status', InvoiceStatus::VALIDATED)
                    ->where('due_date', '<', now())
                    ->orderBy('due_date')
            )
            ->columns([
                TextColumn::make('reference')
                    ->label('Référence')
                    ->weight('bold')
                    ->fontFamily('mono'),

                TextColumn::make('client.name')
                    ->label('Client'),

                TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->color('danger'),

                TextColumn::make('days_overdue')
                    ->label('Jours de retard')
                    ->getStateUsing(fn ($record) => now()->diffInDays($record->due_date))
                    ->color('danger')
                    ->weight('bold'),

                TextColumn::make('total_ht')
                    ->label('Montant HT')
                    ->money('EUR')
                    ->weight('bold'),
            ])
            ->paginated(10);
    }
}
