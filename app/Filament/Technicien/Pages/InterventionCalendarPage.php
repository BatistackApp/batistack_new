<?php

namespace App\Filament\Technicien\Pages;

use App\Enums\Interventions\InterventionStatus;
use App\Enums\Interventions\InterventionType;
use App\Models\Interventions\Intervention;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use UnitEnum;

class InterventionCalendarPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = Phosphor::CalendarBlank;

    protected static ?string $navigationLabel = 'Planning';

    protected static ?string $title = 'Planning des interventions';

    protected static ?string $slug = 'interventions-calendar';

    protected static UnitEnum|string|null $navigationGroup = 'Interventions';

    protected static ?int $navigationSort = 5;

    public function table(Table $table): Table
    {
        $employeeId = auth()->user()?->salarie?->id;

        return $table
            ->query(
                Intervention::query()
                    ->whereHas('workers', fn ($q) => $q->where('employee_id', $employeeId))
                    ->with(['thirdParty', 'chantier'])
                    ->whereNotIn('status', [InterventionStatus::BROUILLON, InterventionStatus::ANNULEE])
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
                    ->limit(25),

                TextColumn::make('chantier.name')
                    ->label('Chantier')
                    ->searchable()
                    ->limit(25),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('scheduled_at')
                    ->label('Prévue le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),

                TextColumn::make('completed_at')
                    ->label('Terminée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->defaultSort('scheduled_at', 'asc')
            ->filters([
                Filter::make('scheduled_at')
                    ->label('Date prévue')
                    ->form([
                        DatePicker::make('scheduled_from')
                            ->label('Du')
                            ->native(false),
                        DatePicker::make('scheduled_until')
                            ->label('Au')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['scheduled_from'], fn ($q, $date) => $q->whereDate('scheduled_at', '>=', $date))
                            ->when($data['scheduled_until'], fn ($q, $date) => $q->whereDate('scheduled_at', '<=', $date));
                    }),

                Filter::make('completed_at')
                    ->label('Date complétion')
                    ->form([
                        DatePicker::make('completed_from')
                            ->label('Du')
                            ->native(false),
                        DatePicker::make('completed_until')
                            ->label('Au')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['completed_from'], fn ($q, $date) => $q->whereDate('completed_at', '>=', $date))
                            ->when($data['completed_until'], fn ($q, $date) => $q->whereDate('completed_at', '<=', $date));
                    }),

                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(InterventionStatus::class)
                    ->preload(),

                SelectFilter::make('type')
                    ->label('Type')
                    ->options(InterventionType::class)
                    ->preload(),
            ])
            ->paginated([10, 25, 50]);
    }
}
