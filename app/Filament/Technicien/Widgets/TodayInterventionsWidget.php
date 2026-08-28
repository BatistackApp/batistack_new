<?php

namespace App\Filament\Technicien\Widgets;

use App\Enums\Interventions\InterventionStatus;
use App\Models\Interventions\Intervention;
use Filament\Widgets\TableWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class TodayInterventionsWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Interventions du jour';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $employee = auth()->user()->salarie;

        return $table
            ->query(
                Intervention::whereHas('workers', fn ($q) => $q->where('employee_id', $employee?->id))
                    ->whereDate('scheduled_at', now()->toDateString())
                    ->whereNotIn('status', [InterventionStatus::BROUILLON, InterventionStatus::ANNULEE])
                    ->with(['thirdParty', 'chantier'])
            )
            ->columns([
                TextColumn::make('reference')
                    ->label('Réf.')
                    ->searchable()
                    ->fontFamily('mono')
                    ->sortable(),

                TextColumn::make('thirdParty.name')
                    ->label('Client')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('chantier.name')
                    ->label('Chantier')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('scheduled_at')
                    ->label('Prévue')
                    ->dateTime('H:i')
                    ->sortable(),
            ])
            ->defaultSort('scheduled_at', 'asc')
            ->paginated([5, 10]);
    }
}
