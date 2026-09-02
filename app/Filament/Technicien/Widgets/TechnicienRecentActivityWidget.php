<?php

namespace App\Filament\Technicien\Widgets;

use App\Enums\Interventions\InterventionStatus;
use App\Models\Interventions\Intervention;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TechnicienRecentActivityWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Interventions récentes';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $employee = auth()->user()->salarie;

        return $table
            ->query(
                Intervention::whereHas('workers', fn ($q) => $q->where('employee_id', $employee?->id))
                    ->whereNotIn('status', [InterventionStatus::BROUILLON, InterventionStatus::ANNULEE])
                    ->with(['thirdParty', 'chantier'])
                    ->latest('updated_at')
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

                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('scheduled_at')
                    ->label('Prévue')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label('Complétée')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->defaultSort('updated_at', 'desc')
            ->paginated([5]);
    }
}
