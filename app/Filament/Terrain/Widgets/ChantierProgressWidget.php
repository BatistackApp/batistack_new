<?php

namespace App\Filament\Terrain\Widgets;

use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use App\Services\Chantiers\ChantierAnalyticService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ChantierProgressWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Progression des Chantiers';

    public function table(Table $table): Table
    {
        $employee = auth()->user()->salarie;

        return $table
            ->query(
                Chantier::forEmployee($employee)
                    ->whereIn('status', [ChantierStatus::IN_PROGRESS, ChantierStatus::AWAITING_RECEPTION])
                    ->with(['manager'])
            )
            ->columns([
                TextColumn::make('reference')
                    ->label('Réf.')
                    ->fontFamily('mono')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Chantier')
                    ->searchable()
                    ->wrap()
                    ->description(fn (Chantier $record) => $record->manager?->full_name ?? 'Sans responsable'),

                TextColumn::make('progress_percent')
                    ->label('Avancement')
                    ->getStateUsing(function (Chantier $record) {
                        $progress = app(ChantierAnalyticService::class)
                            ->getPerformanceMetrics($record)['progress'];

                        return $progress.' %';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        (float) str_replace(' %', '', $state) >= 100 => 'success',
                        (float) str_replace(' %', '', $state) >= 50 => 'primary',
                        (float) str_replace(' %', '', $state) > 0 => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('hours_summary')
                    ->label('Heures')
                    ->getStateUsing(function (Chantier $record) {
                        $metrics = app(ChantierAnalyticService::class)
                            ->getPerformanceMetrics($record);

                        return number_format($metrics['hours']['real'], 0, ',', ' ')
                            .' / '
                            .number_format($metrics['hours']['budget'], 0, ',', ' ');
                    })
                    ->description(function (Chantier $record) {
                        $metrics = app(ChantierAnalyticService::class)
                            ->getPerformanceMetrics($record);

                        $percent = $metrics['hours']['percent'];

                        return number_format($percent, 1, ',', ' ').' % consommé';
                    })
                    ->color(function (Chantier $record) {
                        $metrics = app(ChantierAnalyticService::class)
                            ->getPerformanceMetrics($record);

                        return $metrics['hours']['percent'] > 90 ? 'danger' : null;
                    }),

                TextColumn::make('end_date_preview')
                    ->label('Fin prévue')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'gray'),
            ])
            ->defaultSort('name');
    }
}
