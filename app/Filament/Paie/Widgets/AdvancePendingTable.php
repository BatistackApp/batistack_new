<?php

namespace App\Filament\Paie\Widgets;

use App\Enums\Paie\AdvancePaymentStatus;
use App\Models\Paie\AdvancePayment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class AdvancePendingTable extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('🚨 Acomptes à Traiter')
            ->query(fn (): Builder => AdvancePayment::query()
                ->where('status', AdvancePaymentStatus::PENDING)
                ->with('employee'))
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('request_date')
                    ->label('Date de demande')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('type')
                    ->label("Type d'acompte")
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Montant')
                    ->money('eur')
                    ->sortable(),
            ])
            ->emptyStateHeading('Aucun acompte en attente');
    }
}
