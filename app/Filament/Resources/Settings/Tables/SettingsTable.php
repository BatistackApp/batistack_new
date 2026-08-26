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
                    ->color('gray')
                    ->formatStateUsing(function (string $state, Setting $record): string {
                        return match ($record->type) {
                            'boolean' => $state ? 'Activé' : 'Désactivé',
                            'color' => '<div class="flex items-center gap-2"><div style="width: 1rem; height: 1rem; border-radius: 9999px; background-color: '.$state.'"></div>'.$state.'</div>',
                            'json' => '{ JSON... }',
                            default => $state,
                        };
                    })
                    ->html(),

                TextColumn::make('updated_at')->label('Mis à jour le')
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
