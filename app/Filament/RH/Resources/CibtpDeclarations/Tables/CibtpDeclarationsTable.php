<?php

namespace App\Filament\RH\Resources\CibtpDeclarations\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CibtpDeclarationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('chantier.name')
                    ->label('Chantier')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('weatherAlert.type')
                    ->label('Cause (Météo)')
                    ->badge()
                    ->color('danger')
                    ->placeholder('Saisie Manuelle'),

                TextColumn::make('total_lost_hours')
                    ->label('Heures Perdues')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'warning',
                        'validated' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Brouillon',
                        'submitted' => 'Soumise',
                        'validated' => 'Validée',
                        default => $state,
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filtrer par statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'submitted' => 'Soumise',
                        'validated' => 'Validée',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('print')
                    ->label('Imprimer')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->action(fn () => Notification::make()->title('Génération PDF en cours...')->success()->send()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
