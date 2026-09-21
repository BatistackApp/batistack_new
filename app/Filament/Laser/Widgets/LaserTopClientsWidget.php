<?php

namespace App\Filament\Laser\Widgets;

use App\Enums\Laser\InvoiceStatus;
use App\Models\Laser\LaserInvoice;
use App\Models\Tiers\ThirdParty;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LaserTopClientsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected static ?string $heading = 'Top Clients (CA de l\'année)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ThirdParty::query()
                    ->join('laser_invoices', 'third_parties.id', '=', 'laser_invoices.client_id')
                    ->selectRaw('third_parties.id, third_parties.name, SUM(laser_invoices.total_ht) as total_revenue')
                    ->whereIn('laser_invoices.status', [InvoiceStatus::VALIDATED, InvoiceStatus::PAID])
                    ->whereYear('laser_invoices.created_at', now()->year)
                    ->groupBy('third_parties.id', 'third_parties.name')
                    ->limit(5)
            )
            ->defaultSort('total_revenue', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Client'),

                TextColumn::make('total_revenue')
                    ->label('CA (HT)')
                    ->money('EUR'),
            ])
            ->paginated(false);
    }
}
