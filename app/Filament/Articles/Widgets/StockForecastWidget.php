<?php

namespace App\Filament\Articles\Widgets;

use App\Models\Articles\StockForecast;
use Filament\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class StockForecastWidget extends TableWidget
{
    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Prévisions de ruptures (IA heuristique)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StockForecast::query()
                    ->with(['item', 'item.supplier'])
                    ->where('suggested_qty', '>', 0)
                    ->orderByRaw('CASE WHEN days_until_rupture IS NULL THEN 9999 ELSE days_until_rupture END ASC')
                    ->limit(20)
            )
            ->columns([
                TextColumn::make('item.reference')->label('Réf.')->fontFamily('mono'),
                TextColumn::make('item.name')->label('Article')->limit(30)->searchable(),
                TextColumn::make('item.supplier.name')->label('Fournisseur')->placeholder('—'),
                TextColumn::make('available_stock')->label('Stock disp.')->numeric(decimalPlaces: 2),
                TextColumn::make('daily_burn')->label('Conso/j')->numeric(decimalPlaces: 2),
                TextColumn::make('planned_needs')->label('Besoins BIM (60j)')->numeric(decimalPlaces: 2)->toggleable(),
                TextColumn::make('seasonality_coeff')->label('Coeff saison.')->numeric(decimalPlaces: 2)->toggleable(),
                TextColumn::make('days_until_rupture')->label('J avant rupture')
                    ->numeric()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state <= 7 => 'danger',
                        $state <= 14 => 'warning',
                        default => 'success',
                    })
                    ->suffix(' j'),
                TextColumn::make('suggested_qty')->label('Qté suggérée')->numeric(decimalPlaces: 2)->color('primary')->weight('bold'),
                TextColumn::make('suggested_order_date')->label('Commander avant')->date('d/m/Y')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'gray'),
                BadgeColumn::make('confidence')->label('Confiance')
                    ->colors([
                        'danger' => 'low',
                        'warning' => 'med',
                        'success' => 'high',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'low' => 'Faible',
                        'med' => 'Moyenne',
                        'high' => 'Élevée',
                        default => $state,
                    }),
                TextColumn::make('forecasted_at')->label('Calculé le')->dateTime('d/m H:i')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('view_item')
                    ->label('Fiche')
                    ->icon(Phosphor::Eye)
                    ->url(fn ($record) => "/articles/items/{$record->item_id}"),
            ])
            ->emptyStateHeading('Aucune prévision de rupture')
            ->emptyStateDescription('Lancez `php artisan articles:forecast-stock` pour générer les prévisions.')
            ->paginated(false);
    }
}
