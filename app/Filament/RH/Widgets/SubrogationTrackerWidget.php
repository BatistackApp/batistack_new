<?php

namespace App\Filament\RH\Widgets;

use App\Models\RH\Abscence;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class SubrogationTrackerWidget extends BaseWidget
{
    protected static ?string $heading = 'Suivi des Indemnités Journalières (Subrogation)';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 6;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Abscence::query()
                    ->with('employee')
                    ->pendingSubrogation()
                    ->orderByDate('desc')
            )
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('type')
                    ->label('Type d\'arrêt')
                    ->badge(),
                TextColumn::make('period')
                    ->label('Période')
                    ->getStateUsing(fn (Abscence $record) => "Du {$record->start_date->format('d/m/Y')} au {$record->end_date->format('d/m/Y')}"),
                TextColumn::make('ij_expected')
                    ->label('IJ Attendues')
                    ->money('EUR')
                    ->color('warning'),
                TextColumn::make('ij_received')
                    ->label('IJ Reçues')
                    ->money('EUR')
                    ->color('success'),
                TextColumn::make('balance')
                    ->label('Reste à percevoir')
                    ->getStateUsing(fn (Abscence $record) => $record->getIJBalance())
                    ->money('EUR')
                    ->color('danger')
                    ->weight('bold'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Saisir Paiement')
                    ->icon(Phosphor::CurrencyEur)
                    ->schema([
                        TextInput::make('ij_received')
                            ->label('Montant total reçu (€)')
                            ->numeric()
                            ->required(),
                        DatePicker::make('ij_payment_date')
                            ->label('Date du dernier virement')
                            ->native(false)
                            ->required(),
                    ]),
            ]);
    }
}
