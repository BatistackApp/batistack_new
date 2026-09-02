<?php

namespace App\Filament\Terrain\Pages;

use App\Enums\RH\TimeEntryStatus;
use App\Models\Chantiers\Chantier;
use App\Models\RH\TimeEntry;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use UnitEnum;

class TimeTrackingValidationPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = Phosphor::CheckCircle;

    protected static ?string $navigationLabel = 'Validation Pointages';

    protected static ?string $title = 'Validation des Pointages';

    protected static ?string $slug = 'validation-pointages';

    protected static UnitEnum|string|null $navigationGroup = 'Terrain';

    protected static ?int $navigationSort = 4;

    public function table(Table $table): Table
    {
        $employee = auth()->user()->salarie;

        return $table
            ->query(
                TimeEntry::query()
                    ->where('status', TimeEntryStatus::SUBMITTED)
                    ->whereHas('chantier', fn ($q) => $q->forEmployee($employee))
                    ->with(['employee', 'chantier'])
            )
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Collaborateur')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('chantier.name')
                    ->label('Chantier')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('hours')
                    ->label('Heures')
                    ->suffix(' h')
                    ->sortable(),

                TextColumn::make('travel_hours')
                    ->label('Trajet')
                    ->suffix(' h')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('chantier_id')
                    ->label('Chantier')
                    ->options(fn () => Chantier::forEmployee($employee)->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('date')
                    ->label('Période')
                    ->options([
                        'today' => 'Aujourd\'hui',
                        'week' => 'Cette semaine',
                        'month' => 'Ce mois',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'today' => $query->whereDate('date', now()),
                            'week' => $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]),
                            'month' => $query->whereMonth('date', now()->month)
                                ->whereYear('date', now()->year),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approuver')
                    ->icon(Phosphor::CheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (TimeEntry $record): void {
                        $record->update([
                            'status' => TimeEntryStatus::APPROVED,
                            'approved_by_id' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Pointage approuvé')
                            ->body("Les {$record->hours}h de {$record->employee->full_name} ont été validées.")
                            ->success()
                            ->send();
                    }),

                Action::make('refuse')
                    ->label('Refuser')
                    ->icon(Phosphor::XCircle)
                    ->color('danger')
                    ->schema([
                        Textarea::make('refusal_reason')
                            ->label('Motif du refus')
                            ->required()
                            ->placeholder('Ex: Heures incohérentes, absence non justifiée...'),
                    ])
                    ->action(function (TimeEntry $record, array $data): void {
                        $record->update([
                            'status' => TimeEntryStatus::DRAFT,
                            'refusal_reason' => $data['refusal_reason'],
                        ]);

                        Notification::make()
                            ->title('Pointage refusé')
                            ->body("Le pointage de {$record->employee->full_name} a été renvoyé en brouillon.")
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('approve_all')
                        ->label('Tout approuver')
                        ->icon(Phosphor::CheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update([
                                    'status' => TimeEntryStatus::APPROVED,
                                    'approved_by_id' => auth()->id(),
                                    'approved_at' => now(),
                                ]);
                                $count++;
                            }

                            Notification::make()
                                ->title("{$count} pointage(s) approuvé(s)")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->paginated([15, 30, 50]);
    }
}
