<?php

namespace App\Filament\Laser\Widgets;

use App\Enums\Laser\OrderStatus;
use App\Models\Laser\LaserOrder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LaserRecentOrdersWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected static ?string $heading = 'Commandes récentes';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                LaserOrder::query()
                    ->with('client')
                    ->where('status', '!=', OrderStatus::DRAFT)
                    ->latest()
            )
            ->columns([
                TextColumn::make('reference')
                    ->label('Référence')
                    ->weight('bold')
                    ->fontFamily('mono'),

                TextColumn::make('client.name')
                    ->label('Client')
                    ->limit(20),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('total_ht')
                    ->label('Total HT')
                    ->money('EUR'),
            ])
            ->paginated(5);
    }
}
