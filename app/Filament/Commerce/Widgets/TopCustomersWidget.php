<?php

namespace App\Filament\Commerce\Widgets;

use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;

class TopCustomersWidget extends BaseWidget
{
    protected static ?int $sort = 7;
    // Laissons Filament gérer la taille selon l'écran. 1 colonne sur 2 pour les grands écrans.
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];
    
    protected static ?string $heading = 'Top Clients (CA Facturé de l\'année)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\Tiers\ThirdParty::query()
                    ->join('customer_invoices', 'third_parties.id', '=', 'customer_invoices.client_id')
                    ->selectRaw('third_parties.id, third_parties.name, SUM(customer_invoices.total_ht) as total_revenue')
                    ->whereIn('customer_invoices.status', [\App\Enums\Commerce\InvoiceStatus::VALIDATED, \App\Enums\Commerce\InvoiceStatus::PAID])
                    ->whereYear('customer_invoices.created_at', now()->year)
                    ->groupBy('third_parties.id')
                    ->limit(5)
            )
            ->defaultSort('total_revenue', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Client'),
                TextColumn::make('total_revenue')
                    ->label('Chiffre d\'affaires (HT)')
                    ->money('EUR'),
            ])
            ->paginated(false);
    }
}
