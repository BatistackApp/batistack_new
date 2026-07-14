<?php

namespace App\Filament\Banque\Widgets;

use App\Models\Banque\BankAccount;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class BankAccountsStatusList extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'État des synchronisations';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => BankAccount::query())
            ->columns([
                TextColumn::make('name')->label('Compte'),
                TextColumn::make('balance')->label('Solde')->numeric()->suffix(' €'),
                TextColumn::make('updated_at')->label('Dernière synchro')->since()->badge()->color(fn ($record) => $record->updated_at > now()->subDay() ? 'success' : 'warning'),
            ])
            ->paginated(false);
    }
}
