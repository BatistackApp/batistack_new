<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FixedAssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('purchase_date')
                    ->label('Date achat')
                    ->date()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Valeur brute')
                    ->money('EUR')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\ViewAction::make(),
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\Action::make('dispose')
                    ->label('Céder / Rebut')
                    ->icon('heroicon-o-archive-box-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('disposal_date')->label('Date de sortie')->required()->default(now()),
                        \Filament\Forms\Components\TextInput::make('sale_price')->label('Prix de cession')->numeric()->default(0)->required()->prefix('€'),
                        \Filament\Forms\Components\TextInput::make('reason')->label('Raison (Revente, Vol, Rebut)')->required(),
                    ])
                    ->action(function (\App\Models\Immobilisation\FixedAsset $record, array $data) {
                        $service = new \App\Services\Immobilisation\AssetDisposalService();
                        $service->dispose($record, $data['disposal_date'], $data['sale_price'], $data['reason']);
                    })
                    ->visible(fn (\App\Models\Immobilisation\FixedAsset $record) => $record->status !== \App\Enums\Immobilisation\AssetStatus::DISPOSED),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
