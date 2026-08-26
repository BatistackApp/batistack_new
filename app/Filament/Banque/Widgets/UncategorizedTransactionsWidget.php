<?php

namespace App\Filament\Banque\Widgets;

use App\Filament\Banque\Resources\Banque\BankTransactions\BankTransactionResource;
use App\Models\Banque\BankTransaction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UncategorizedTransactionsWidget extends TableWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Transactions non catégorisées';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => BankTransaction::query()->whereNull('transaction_category_id')->latest('date'))
            ->columns([
                TextColumn::make('bankAccount.name')->label('Compte'),
                TextColumn::make('date')->label('Date')->date('d/m/Y')->sortable(),
                TextColumn::make('description')->label('Description')->limit(50),
                TextColumn::make('amount')->label('Montant')->numeric()->sortable()->badge()->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
            ])
            ->recordActions([
                Action::make('categoriser')
                    ->label('Catégoriser')
                    ->icon('heroicon-o-tag')
                    ->url(fn (BankTransaction $record) => BankTransactionResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
