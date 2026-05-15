<?php

namespace App\Filament\RH\Widgets;

use App\Models\RH\Contract;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ExpiringContractsWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Fins de Contrats Imminentes (< 30j)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Contract::whereNotNull('end_date')
                    ->where('end_date', '<=', now()->addDays(30))
                    ->with('employee')
            )
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Salarié'),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('job_title')
                    ->label('Poste'),
                TextColumn::make('end_date')
                    ->label('Date de fin')
                    ->date('d/m/Y')
                    ->color('warning')
                    ->weight('bold'),
                TextColumn::make('days_left')
                    ->label('Urgence')
                    ->getStateUsing(fn ($record) => $record->end_date->isPast() ? 'Terminé' : 'J-'.now()->diffInDays($record->end_date))
                    ->badge()
                    ->color(fn ($state) => $state === 'Terminé' ? 'danger' : 'warning'),
            ])
            ->recordActions([
                Action::make('manage')
                    ->label('Gérer')
                    ->icon(Phosphor::ArrowRight)
                    ->url(fn ($record) => "/rh/employees/{$record->employee_id}/edit"),
            ]);
    }
}
