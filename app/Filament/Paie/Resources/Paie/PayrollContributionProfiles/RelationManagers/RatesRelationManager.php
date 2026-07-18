<?php

namespace App\Filament\Paie\Resources\Paie\PayrollContributionProfiles\RelationManagers;

use App\Enums\Paie\ContributionBaseFormula;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RatesRelationManager extends RelationManager
{
    protected static string $relationship = 'rates';

    protected static ?string $title = 'Taux de Cotisations';

    protected static ?string $modelLabel = 'Taux de Cotisation';

    protected static ?string $pluralModelLabel = 'Taux de Cotisations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('category')
                    ->label('Catégorie (ex: Santé, Retraite)')
                    ->required()
                    ->maxLength(255),
                TextInput::make('label')
                    ->label('Libellé')
                    ->required()
                    ->maxLength(255),
                TextInput::make('employee_rate')
                    ->label('Taux Salarial (%)')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('employer_rate')
                    ->label('Taux Patronal (%)')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Select::make('base_formula')
                    ->label('Base de calcul')
                    ->options(ContributionBaseFormula::class)
                    ->required()
                    ->default(ContributionBaseFormula::GROSS_SALARY),
                Forms\Components\Toggle::make('is_deductible')
                    ->label('Déductible ?')
                    ->default(true)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('category')
            ->columns([
                TextColumn::make('category')
                    ->label('Catégorie')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('label')
                    ->label('Libellé')
                    ->searchable(),
                TextColumn::make('employee_rate')
                    ->label('Taux Salarial (%)')
                    ->sortable(),
                TextColumn::make('employer_rate')
                    ->label('Taux Patronal (%)')
                    ->sortable(),
                TextColumn::make('base_formula')
                    ->label('Base')
                    ->badge(),
                IconColumn::make('is_deductible')
                    ->label('Déductible ?')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
