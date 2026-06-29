<?php

namespace App\Filament\Widgets;

use App\Models\Chantiers\Chantier;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestChantiersWidget extends TableWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Derniers Chantiers';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Chantier::query()->latest()->limit(5))
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->label('Nom du Chantier')
                    ->searchable(),
                TextColumn::make('client.name')
                    ->label('Client'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('start_date')
                    ->date('d/m/Y')
                    ->label('Début'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
