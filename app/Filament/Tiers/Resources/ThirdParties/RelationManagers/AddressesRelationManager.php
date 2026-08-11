<?php

namespace App\Filament\Tiers\Resources\ThirdParties\RelationManagers;

use App\Enums\Tiers\AddressType;
use BackedEnum;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';
    protected static ?string $title = 'Adresses & Localisation';
    protected static string | BackedEnum | null $icon = Phosphor::MapPin;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')->label('Type')
                    ->label('Type d\'adresse')
                    ->options(AddressType::class)
                    ->required()
                    ->native(false),
                TextInput::make('street')
                    ->label('Rue / Voie')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('zip_code')->label('Code postal')
                    ->label('Code Postal')
                    ->required(),
                TextInput::make('city')->label('Ville')
                    ->label('Ville')
                    ->required(),
                TextInput::make('country')->label('Pays')
                    ->label('Pays')
                    ->default('France')
                    ->required(),
                Toggle::make('is_default')
                    ->label('Adresse par défaut')
                    ->onColor('success'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                TextColumn::make('type')->label('Type')->badge(),
                TextColumn::make('full_address')
                    ->label('Adresse complète'),
                IconColumn::make('is_default')
                    ->label('Défaut')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
