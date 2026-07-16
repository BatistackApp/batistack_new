<?php

namespace App\Filament\Chantier\Resources\Chantiers\RelationManagers;

use App\Enums\Commerce\InvoiceStatus;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';
    protected static string | BackedEnum | null $icon = Phosphor::Receipt;
    protected static ?string $title = 'Factures de Situation';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('Réf. Facture')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('total_ht')
                    ->label('Montant HT')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('total_ttc')
                    ->label('Montant TTC')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('payment_percentage')
                    ->label('Payé (%)')
                    ->getStateUsing(fn ($record) => number_format($record->payment_percentage, 0) . ' %')
                    ->badge()
                    ->color(fn ($record) => $record->payment_percentage >= 100 ? 'success' : ($record->payment_percentage > 0 ? 'warning' : 'danger')),
                TextColumn::make('created_at')
                    ->label('Émise le')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->headerActions([
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record): string => \App\Filament\Commerce\Resources\CustomerInvoices\CustomerInvoiceResource::getUrl('view', ['record' => $record], panel: 'commerce')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
