<?php

namespace App\Filament\Flottes\Resources\Vehicles\RelationManagers;

use App\Models\Core\VatRate;
use App\Models\Tiers\ThirdParty;
use BackedEnum;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class MaintenancesRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenances';

    protected static ?string $title = 'Carnet d\'Entretien & Révisions';

    protected static string|BackedEnum|null $icon = Phosphor::Wrench;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Enregistrement de l\'Intervention')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('supplier_id')
                            ->label('Partenaire / Garage (Fournisseur)')
                            ->options(ThirdParty::where('type', 'supplier')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('vat_rate_id')
                            ->label('Taux de TVA')
                            ->options(VatRate::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->native(false),
                        TextInput::make('type')->label('Type')
                            ->label('Nature des travaux')
                            ->required()
                            ->placeholder('ex: Vidange complète, Remplacement embrayage'),
                        TextInput::make('cost_ht')
                            ->label('Montant HT de la facture')
                            ->numeric()
                            ->prefix('€')
                            ->required(),
                        DatePicker::make('performed_at')
                            ->label('Date d\'intervention')
                            ->required()
                            ->native(false)
                            ->default(now()),
                        TextInput::make('odometer_at_maintenance')
                            ->label('Compteur relevé au garage')
                            ->numeric()
                            ->suffix(fn () => $this->getOwnerRecord()->usage_unit === 'hours' ? 'h' : 'km')
                            ->helperText('Permet de réinitialiser le jalon de révision préventive.'),
                        Textarea::make('description')->label('Description')
                            ->label('Détail des réparations / pièces changées')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                TextColumn::make('performed_at')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('type')->label('Type')
                    ->label('Nature des travaux')
                    ->weight('bold'),
                TextColumn::make('supplier.name')
                    ->label('Garage / Atelier'),
                TextColumn::make('odometer_at_maintenance')
                    ->label('Kilométrage relevé')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(fn () => $this->getOwnerRecord()->usage_unit === 'hours' ? ' h' : ' km'),
                TextColumn::make('cost_ht')
                    ->label('Facture HT')
                    ->money('EUR')
                    ->weight('semibold'),
            ])
            ->headerActions([
                CreateAction::make()->label('Nouvelle Intervention'),
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
