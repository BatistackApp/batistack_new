<?php

namespace App\Filament\Chantier\Widgets;

use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use App\Services\Chantiers\ChantierAnalyticService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ActiveChantiersTable extends TableWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Suivi d\'Avancement des Chantiers Actifs';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Chantier::where('status', ChantierStatus::IN_PROGRESS)
                    ->with(['manager', 'client'])
            )
            ->columns([
                TextColumn::make('reference')
                    ->label('Réf.')
                    ->fontFamily('mono'),

                TextColumn::make('name')
                    ->label('Désignation')
                    ->wrap()
                    ->description(fn (Chantier $record) => $record->manager?->full_name ?? 'Sans responsable'),

                TextColumn::make('progress_percent')
                    ->label('Avancement')
                    ->getStateUsing(function (Chantier $record) {
                        return app(ChantierAnalyticService::class)->getPerformanceMetrics($record)['progress'].' %';
                    })
                    ->badge()
                    ->color(fn ($state) => (float) $state >= 100 ? 'success' : 'primary'),

                TextColumn::make('end_date_preview')
                    ->label('Fin prévue')
                    ->date('d/m/y')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'gray'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => "/chantier/chantiers/{$record->id}"),
            ]);
    }
}
