<?php

namespace App\Filament\Interventions;

use App\Filament\Interventions\Pages\ClientEquipment\ManageClientEquipment;
use App\Models\Interventions\ClientEquipment;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientEquipmentResource extends Resource
{
    protected static ?string $model = ClientEquipment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'namename';

    protected static ?string $navigationLabel = 'Equipements Clients';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Hidden::make('company_id')
                    ->default(fn () => auth()->user()->company_id ?? 1),
                Select::make('third_party_id')
                    ->label('Client')
                    ->relationship('thirdParty', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')->label('Nom')
                    ->label('Nom de l\'équipement')
                    ->required(),
                TextInput::make('brand')
                    ->label('Marque'),
                TextInput::make('serial_number')
                    ->label('Numéro de série'),
                DatePicker::make('installation_date')
                    ->label('Date d\'installation'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('thirdParty.name')
                    ->label('Client')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')->label('Nom')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('brand')
                    ->label('Marque')
                    ->searchable(),
                TextColumn::make('serial_number')
                    ->label('N° Série')
                    ->searchable(),
                TextColumn::make('installation_date')
                    ->label('Installation')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
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

    public static function getPages(): array
    {
        return [
            'index' => ManageClientEquipment::route('/'),
        ];
    }
}
