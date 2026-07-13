<?php

namespace App\Filament\Commerce\Resources\CustomerSituations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class CustomerSituationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('number')->label('N°')->searchable()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('order.reference')->label('Commande')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('chantier.reference')->label('Chantier')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('status')->label('Statut')->badge()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('periode_start')->label('Début')->date('d/m/Y')->sortable(),
                \Filament\Tables\Columns\TextColumn::make('periode_end')->label('Fin')->date('d/m/Y')->sortable(),
                \Filament\Tables\Columns\TextColumn::make('total_ht')->label('Total HT')->numeric()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('total_ttc')->label('Total TTC')->numeric()->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(\App\Enums\Commerce\InvoiceStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
