<?php

namespace App\Filament\Commerce\Widgets;

use App\Models\Commerce\CustomerInvoice;
use App\Enums\Commerce\InvoiceStatus;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Carbon;

class OverdueInvoicesWidget extends BaseWidget
{
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];
    protected static ?string $heading = 'Factures Clients en Retard';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CustomerInvoice::query()
                    ->where('status', InvoiceStatus::VALIDATED)
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', Carbon::now())
                    ->orderBy('due_date', 'asc')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('reference')
                    ->label('Facture'),
                TextColumn::make('client.name')
                    ->label('Client'),
                TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->color('danger'),
                TextColumn::make('total_ttc')
                    ->label('Total TTC')
                    ->money('EUR'),
            ])
            ->paginated(false);
    }
}
