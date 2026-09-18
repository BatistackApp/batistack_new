<?php

namespace App\Filament\Laser\Resources\LaserOrders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DeliveryNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'deliveryNotes';

    protected static ?string $title = 'Bons de livraison';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->url(fn ($record) => route('filament.laser.resources.laser-delivery-notes.view', $record)),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('delivery_date')
                    ->label('Date de livraison')
                    ->date('d/m/Y'),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ]);
    }
}
