<?php

namespace App\Filament\RH\Widgets;

use App\Models\RH\Qualification;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ExpiringQualificationsWidget extends TableWidget
{
    protected static ?int $sort = 2;


    protected static ?string $heading = 'Alertes Sécurité & Habilitations (Échéance < 60j)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Qualification::where('expires_at', '<=', now()->addDays(60))
                    ->with('employee')
            )
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Collaborateur'),
                TextColumn::make('label')
                    ->label('Habilitation'),
                TextColumn::make('expires_at')
                    ->label('Date d\'échéance')
                    ->date('d/m/Y')
                    ->color(fn ($state) => $state->isPast() ? 'danger' : 'warning')
                    ->weight('bold'),
                TextColumn::make('days_left')
                    ->label('Délai')
                    ->getStateUsing(fn ($record) => $record->expires_at->isPast() ? 'Expiré' : 'J-'.now()->diffInDays($record->expires_at))
                    ->badge()
                    ->color(fn ($state) => $state === 'Expiré' ? 'danger' : 'warning'),
            ])
            ->recordActions([
                Action::make('view_employee')
                    ->label('Gérer')
                    ->icon(Phosphor::ArrowRight)
                    ->url(fn ($record) => "/rh/employees/{$record->employee_id}/edit"),
            ]);
    }
}
