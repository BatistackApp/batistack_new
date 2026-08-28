<?php

namespace App\Filament\RH\Widgets;

use App\Models\RH\TimeEntry;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TimeEntryAnomaliesWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Anomalie de Pointage';

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('Aucune Anomalie de Pointage')
            ->query(
                TimeEntry::query()
                    ->where('is_anomaly', true)
                    ->whereNull('anomaly_resolved_at')
                    ->latest('date')
            )
            ->columns([
                TextColumn::make('employee.first_name')
                    ->label('Employé')
                    ->formatStateUsing(fn ($record) => $record->employee->getFullName()),
                TextColumn::make('date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('hours')
                    ->label('Heures')
                    ->numeric(),
                TextColumn::make('anomaly_reason')
                    ->label('Motif d\'anomalie')
                    ->wrap()
                    ->color('danger'),
            ])
            ->recordActions([
                Action::make('resolve')
                    ->label('Résoudre')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (TimeEntry $record) {
                        $record->update([
                            'anomaly_resolved_at' => now(),
                            'anomaly_resolved_by_id' => auth()->id(),
                        ]);
                    }),
            ]);
    }
}
