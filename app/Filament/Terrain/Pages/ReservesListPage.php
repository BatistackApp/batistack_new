<?php

namespace App\Filament\Terrain\Pages;

use App\Enums\Chantiers\ChantierReserveStatus;
use App\Enums\Chantiers\ReserveSeverity;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierReserve;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ReservesListPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = Phosphor::Warning;

    protected static ?string $navigationLabel = 'Réserves';

    protected static ?string $title = 'Suivi des Réserves';

    protected static ?string $slug = 'reserves';

    protected static UnitEnum|string|null $navigationGroup = 'Terrain';

    protected static ?int $navigationSort = 2;

    public function table(Table $table): Table
    {
        $employee = auth()->user()->salarie;

        return $table
            ->query(
                ChantierReserve::query()
                    ->whereHas('chantier', fn ($q) => $q->forEmployee($employee))
                    ->with(['chantier', 'assignee'])
            )
            ->columns([
                TextColumn::make('reference')
                    ->label('Réf.')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('chantier.name')
                    ->label('Chantier')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('severity')
                    ->label('Gravité')
                    ->badge(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('assignee.full_name')
                    ->label('Assigné à')
                    ->placeholder('Non assigné'),

                TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : null),

                TextColumn::make('created_at')
                    ->label('Créée le')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(ChantierReserveStatus::class)
                    ->preload(),
                SelectFilter::make('severity')
                    ->label('Gravité')
                    ->options(ReserveSeverity::class)
                    ->preload(),
                SelectFilter::make('chantier_id')
                    ->label('Chantier')
                    ->options(fn () => Chantier::forEmployee($employee)->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('start')
                    ->label('Prendre en charge')
                    ->icon(Phosphor::Play)
                    ->color('warning')
                    ->visible(fn (ChantierReserve $record) => $record->status === ChantierReserveStatus::OPEN)
                    ->requiresConfirmation()
                    ->action(function (ChantierReserve $record): void {
                        $record->update([
                            'status' => ChantierReserveStatus::IN_PROGRESS,
                        ]);
                        Notification::make()
                            ->title('Réserve prise en charge')
                            ->success()
                            ->send();
                    }),

                Action::make('resolve')
                    ->label('Marquer résolue')
                    ->icon(Phosphor::CheckCircle)
                    ->color('success')
                    ->visible(fn (ChantierReserve $record) => in_array($record->status, [
                        ChantierReserveStatus::OPEN,
                        ChantierReserveStatus::IN_PROGRESS,
                    ]))
                    ->schema([
                        DatePicker::make('resolved_at')
                            ->label('Date de résolution')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function (ChantierReserve $record, array $data): void {
                        $record->update([
                            'status' => ChantierReserveStatus::RESOLVED,
                            'resolved_at' => $data['resolved_at'],
                        ]);
                        Notification::make()
                            ->title('Réserve marquée comme résolue')
                            ->success()
                            ->send();
                    }),
            ])
            ->paginated([10, 25, 50]);
    }

}
