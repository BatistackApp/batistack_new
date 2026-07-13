<?php

namespace App\Filament\Banque\Widgets;

use App\Enums\Banque\TransactionStatus;
use App\Filament\Banque\Resources\Banque\BankTransactions\BankTransactionResource;
use App\Models\Banque\BankTransaction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PendingTransactionsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Transactions en attente de lettrage';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => BankTransaction::query()->where('status', TransactionStatus::PENDING)->latest('date')->limit(5))
            ->columns([
                TextColumn::make('bankAccount.name')->label('Compte'),
                TextColumn::make('date')->date('d/m/Y')->sortable(),
                TextColumn::make('description')->limit(50),
                TextColumn::make('amount')->numeric()->sortable()->badge()->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
            ])
            ->recordActions([
                \Filament\Tables\Actions\Action::make('lettrer')
                    ->label('Lettrer')
                    ->icon('heroicon-o-link')
                    ->color('success')
                    ->url(fn (BankTransaction $record) => BankTransactionResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
