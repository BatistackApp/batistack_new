<?php

namespace App\Filament\Resources\Settings\Tables;

use App\Models\Core\Setting;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->groups(['group'])
            ->defaultGroup('group')
            ->columns([
                TextColumn::make('key')
                    ->label('Clé de configuration')
                    ->description(fn (Setting $record): string => "Groupe: {$record->group}")
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Clé copiée !'),

                TextColumn::make('group')
                    ->label('Groupe')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge(),

                TextColumn::make('value')
                    ->label('Valeur')
                    ->limit(40)
                    ->wrap()
                    ->color('gray'),

                TextColumn::make('updated_at')
                    ->label('Modifié')
                    ->since()
                    ->sortable(),
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
