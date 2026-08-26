<?php

namespace App\Filament\Banque\Widgets;

use App\Filament\Banque\Resources\Banque\BankTransactions\BankTransactionResource;
use App\Models\Banque\BankTransaction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class HighValueAnomaliesWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Transactions > 1000€ sans justificatif (non lettrées)';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => BankTransaction::query()
                ->whereDoesntHave('reconciliations')
                ->where(function ($query) {
                    $query->where('amount', '>=', 1000)
                        ->orWhere('amount', '<=', -1000);
                })
                ->latest('date')
            )
            ->columns([
                TextColumn::make('bankAccount.name')->label('Compte'),
                TextColumn::make('date')->label('Date')->date('d/m/Y')->sortable(),
                TextColumn::make('description')->label('Description')->limit(50),
                TextColumn::make('amount')->label('Montant')->numeric()->sortable()->badge()->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
            ])
            ->recordActions([
                Action::make('lettrer')
                    ->label('Rapprocher')
                    ->icon('heroicon-o-link')
                    ->color('warning')
                    ->url(fn (BankTransaction $record) => BankTransactionResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
