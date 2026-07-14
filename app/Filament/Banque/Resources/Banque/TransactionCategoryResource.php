<?php

namespace App\Filament\Banque\Resources\Banque;

use App\Filament\Banque\Resources\Banque\TransactionCategoryResource\Pages;
use App\Models\Banque\TransactionCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransactionCategoryResource extends Resource
{
    protected static ?string $model = TransactionCategory::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-tag';
    protected static \UnitEnum|string|null $navigationGroup = 'Banque';
    protected static ?string $modelLabel = 'Catégorie';
    protected static ?string $pluralModelLabel = 'Catégories de transaction';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Nom de la catégorie')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->label('Type')
                    ->options([
                        'expense' => 'Dépense',
                        'income' => 'Revenu',
                    ])
                    ->default('expense')
                    ->required(),
                ColorPicker::make('color')
                    ->label('Couleur (Badge)'),

                Repeater::make('rules')
                    ->relationship()
                    ->label('Règles de catégorisation (Mots-clés)')
                    ->schema([
                        TextInput::make('keyword')
                            ->label('Mot-clé')
                            ->required()
                            ->maxLength(255)
                            ->hint("Si le libellé de la transaction contient ce mot, elle sera classée dans cette catégorie."),
                    ])
                    ->columnSpanFull()
                    ->collapsible()
                    ->defaultItems(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')
                    ->label('Couleur'),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'expense' => 'Dépense',
                        'income' => 'Revenu',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'expense' => 'danger',
                        'income' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('rules_count')
                    ->counts('rules')
                    ->label('Mots-clés associés'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->groupedBulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTransactionCategories::route('/'),
        ];
    }
}
