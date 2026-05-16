<?php

namespace App\Filament\Chantier\Resources\Chantiers\Tables;

use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use App\Services\Chantiers\ChantierAnalyticService;
use App\Services\Chantiers\ChantierDocumentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ChantiersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('Réf.')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('name')
                    ->label('Chantier')
                    ->searchable()
                    ->wrap()
                    ->description(fn (Chantier $record) => $record->client->name),
                TextColumn::make('status')
                    ->label('État')
                    ->badge(),
                TextColumn::make('progress')
                    ->label('Avancement')
                    ->getStateUsing(fn (Chantier $record, ChantierAnalyticService $service) => $service->getPerformanceMetrics($record)['progress'].' %')
                    ->badge()
                    ->color(fn ($state) => (float) $state >= 100 ? 'success' : 'primary'),
                TextColumn::make('budget_hours')
                    ->label('Heures')
                    ->description(fn (Chantier $record) => 'Réel : '.$record->real_hours.'h')
                    ->color(fn (Chantier $record) => $record->real_hours > $record->budget_hours ? 'danger' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->options(ChantierStatus::class),
                SelectFilter::make('manager_id')->label('Conducteur')->relationship('manager', 'last_name'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('print_os')
                        ->label('Ordre de Service')
                        ->icon(Phosphor::FilePdf)
                        ->color('info')
                        ->action(fn (Chantier $record, ChantierDocumentService $service) => response()->download($service->generateStartOrder($record))),
                    Action::make('print_bilan')
                        ->label('Bilan Analytique')
                        ->icon(Phosphor::ChartPie)
                        ->color('success')
                        ->action(fn (Chantier $record, ChantierDocumentService $service) => response()->download($service->generateRentabilityReport($record))),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
