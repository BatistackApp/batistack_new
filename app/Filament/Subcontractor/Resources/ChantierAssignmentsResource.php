<?php

namespace App\Filament\Subcontractor\Resources;

use App\Enums\Chantiers\ChantierStatus;
use App\Filament\Subcontractor\Resources\ChantierAssignmentsResource\Pages;
use App\Models\Chantiers\Chantier;
use App\Services\Chantiers\ChantierAnalyticService;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChantierAssignmentsResource extends Resource
{
    protected static ?string $model = Chantier::class;

    protected static ?string $modelLabel = 'Chantier';

    protected static ?string $pluralModelLabel = 'Mes Chantiers';

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user || ! $user->contact || ! $user->contact->thirdParty) {
            return parent::getEloquentQuery()->where('id', 0);
        }

        $subcontractorId = $user->contact->thirdParty->id;

        return parent::getEloquentQuery()
            ->whereHas('subcontractors', function (Builder $query) use ($subcontractorId) {
                $query->where('third_parties.id', $subcontractorId);
            })
            ->with(['phases.tasks']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Désignation')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('address')
                    ->label('Adresse')
                    ->limit(30)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (ChantierStatus $state): string => $state->getColor()),
                Tables\Columns\TextColumn::make('start_date_preview')
                    ->label('Début prévu')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('end_date_preview')
                    ->label('Fin prévue')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('progress')
                    ->label('Avancement')
                    ->getStateUsing(function (Chantier $record) {
                        $service = app(ChantierAnalyticService::class);

                        return $service->getPerformanceMetrics($record)['progress'] ?? 0;
                    })
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state.' %')
                    ->color(fn (int $state): string => match (true) {
                        $state >= 100 => 'success',
                        $state > 0 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(ChantierStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->paginated([10, 25, 50]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChantierAssignments::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return true;
    }
}
