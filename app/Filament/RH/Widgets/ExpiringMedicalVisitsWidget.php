<?php

namespace App\Filament\RH\Widgets;

use App\Models\RH\MedicalVisit;
use App\Notifications\RH\MedicalVisitReminderNotification;
use App\Services\RH\RHDocumentService;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ExpiringMedicalVisitsWidget extends TableWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Visites Médicales à programmer (< 60j)';

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('Aucune visite médicale à programmer actuellement')
            ->query(
                MedicalVisit::where('next_due_date', '<=', now()->addDays(60))
                    ->with('employee')
            )
            ->defaultSort('next_due_date', 'asc')
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Collaborateur'),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('aptitude')
                    ->badge(),
                TextColumn::make('next_due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->color(fn ($state) => $state->isPast() ? 'danger' : ($state->diffInDays(now()) < 30 ? 'warning' : 'success'))
                    ->weight('bold'),
                TextColumn::make('days_left')
                    ->label('Urgence')
                    ->getStateUsing(fn ($record) => $record->next_due_date->isPast() ? 'Dépassée' : 'J-'.now()->diffInDays($record->next_due_date))
                    ->badge()
                    ->color(fn ($state) => $state === 'Dépassée' ? 'danger' : 'warning'),
            ])
            ->recordActions([
                Action::make('remind')
                    ->label('Relancer')
                    ->icon(Phosphor::BellRinging)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (MedicalVisit $record) => auth()->user()->notify(new MedicalVisitReminderNotification($record))),

                Action::make('print_passport')
                    ->label('Passeport Sécurité')
                    ->icon(Phosphor::FilePdf)
                    ->color('gray')
                    ->action(fn (MedicalVisit $record, RHDocumentService $service) => $service->download($service->generateSafetyPassport($record->employee))),

                Action::make('view_employee')
                    ->label('Gérer')
                    ->icon(Phosphor::ArrowRight)
                    ->url(fn ($record) => "/rh/employees/{$record->employee_id}/edit"),
            ]);
    }
}
