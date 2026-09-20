<?php

namespace App\Filament\Laser\Resources\LaserInvoices\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Paiements';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment.reference')
            ->columns([
                TextColumn::make('payment.reference')
                    ->label('Référence')
                    ->searchable(),
                TextColumn::make('allocated_amount')
                    ->label('Montant payé')
                    ->money('EUR'),
                TextColumn::make('payment.method')
                    ->label('Moyen de paiement')
                    ->badge(),
                TextColumn::make('payment.payment_date')
                    ->label('Date')
                    ->date('d/m/Y'),
            ]);
    }
}
