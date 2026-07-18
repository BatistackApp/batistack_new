<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\Tables;

use App\Enums\Immobilisation\AssetStatus;
use App\Models\Immobilisation\FixedAsset;
use App\Services\Immobilisation\AssetDisposalService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FixedAssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->sortable(),
                TextColumn::make('purchase_date')
                    ->label('Date achat')
                    ->date()
                    ->sortable(),
                TextColumn::make('purchase_price')
                    ->label('Valeur brute')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('dispose')
                    ->label('Céder / Rebut')
                    ->icon('heroicon-o-archive-box-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->schema([
                        DatePicker::make('disposal_date')->label('Date de sortie')->required()->default(now()),
                        TextInput::make('sale_price')->label('Prix de cession')->numeric()->default(0)->required()->prefix('€'),
                        TextInput::make('reason')->label('Raison (Revente, Vol, Rebut)')->required(),
                    ])
                    ->action(function (FixedAsset $record, array $data) {
                        $service = new AssetDisposalService;
                        $service->dispose($record, $data['disposal_date'], $data['sale_price'], $data['reason']);
                    })
                    ->visible(fn (FixedAsset $record) => $record->status !== AssetStatus::DISPOSED),
            ])
            ->groupedBulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
