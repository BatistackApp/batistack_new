<?php

namespace App\Filament\Subcontractor\Resources;

use App\Filament\Subcontractor\Resources\AssignedTaskResource\Pages;
use App\Models\Chantiers\ChantierTask;
use App\Models\Tiers\ThirdParty;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssignedTaskResource extends Resource
{
    protected static ?string $model = ChantierTask::class;

    protected static ?string $modelLabel = 'Tâche assignée';

    protected static ?string $pluralModelLabel = 'Tâches assignées';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user || ! $user->contact || ! $user->contact->thirdParty) {
            return parent::getEloquentQuery()->where('id', 0); // No access
        }

        $subcontractorId = $user->contact->thirdParty->id;

        return parent::getEloquentQuery()->whereHas('allocations', function (Builder $query) use ($subcontractorId) {
            $query->where('allocatable_type', ThirdParty::class)
                ->where('allocatable_id', $subcontractorId);
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Read-only fields
                TextInput::make('label')
                    ->label('Tâche')
                    ->disabled(),
                TextInput::make('progress_percentage')
                    ->label('Avancement (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('phase.chantier.reference')
                    ->label('Chantier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phase.name')
                    ->label('Phase')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('label')
                    ->label('Tâche')
                    ->searchable(),
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Avancement')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 100 => 'success',
                        $state > 0 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn ($state) => $state.' %'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Date début')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Date fin')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('update_progress')
                    ->label('Mettre à jour l\'avancement')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->form([
                        TextInput::make('progress_percentage')
                            ->label('Nouveau pourcentage d\'avancement')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(fn (ChantierTask $record) => $record->progress_percentage)
                            ->required()
                            ->suffix('%'),
                    ])
                    ->action(function (ChantierTask $record, array $data): void {
                        $record->update([
                            'progress_percentage' => $data['progress_percentage'],
                            'is_completed' => $data['progress_percentage'] == 100,
                        ]);
                        Notification::make()
                            ->title('Avancement mis à jour')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                // No bulk actions for subcontractors
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssignedTasks::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
