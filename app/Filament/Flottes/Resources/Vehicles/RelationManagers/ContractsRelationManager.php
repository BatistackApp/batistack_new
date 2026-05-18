<?php

namespace App\Filament\Flottes\Resources\Vehicles\RelationManagers;

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
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'contracts';

    protected static ?string $title = 'Contrats & Assurances';

    protected static string|BackedEnum|null $icon = Phosphor::FileText;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Détails du Contrat')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('supplier_id')
                            ->label('Assureur / Loueur (Tiers)')
                            ->options(ThirdParty::where('type', 'supplier')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('type')
                            ->label('Type de contrat')
                            ->options([
                                'insurance' => 'Assurance',
                                'leasing' => 'Leasing / Crédit-Bail',
                                'rental' => 'Location Longue Durée',
                            ])
                            ->required()
                            ->native(false),
                        TextInput::make('policy_number')
                            ->label('N° de Police / Contrat')
                            ->required()
                            ->placeholder('ex: POL-ASS-9988'),
                        TextInput::make('annual_cost_ht')
                            ->label('Coût Annuel HT')
                            ->numeric()
                            ->prefix('€')
                            ->required(),
                        DatePicker::make('start_date')
                            ->label('Date d\'effet')
                            ->required()
                            ->native(false),
                        DatePicker::make('end_date')
                            ->label('Date d\'échéance')
                            ->native(false),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('policy_number')
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'insurance' => 'Assurance',
                        'leasing' => 'Leasing',
                        'rental' => 'LLD / Location',
                        default => $state
                    }),
                TextColumn::make('policy_number')
                    ->label('N° de contrat')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('supplier.name')
                    ->label('Partenaire'),
                TextColumn::make('annual_cost_ht')
                    ->label('Coût Annuel HT')
                    ->money('EUR')
                    ->weight('semibold'),
                TextColumn::make('end_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'gray')
                    ->placeholder('Contrat permanent'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->label('Nouveau contrat'),
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
