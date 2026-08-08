<?php

namespace App\Filament\Commerce\Resources\CustomerInvoices\RelationManagers;

use App\Models\Commerce\PaymentAllocation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'allocations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('reference')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment.reference')
            ->columns([
                TextColumn::make('payment.reference')
                    ->label('Référence du paiement')
                    ->searchable(),
                TextColumn::make('allocated_amount')
                    ->label('Montant alloué')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('payment.payment_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Allouer un paiement')
                    ->icon(Phosphor::PlusCircle)
                    ->mutateDataUsing(function (array $data) {
                        $data['invoice_id'] = $this->getOwnerRecord()->id;
                    })
                    ->using(function (PaymentAllocation $record, array $data) {}),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
