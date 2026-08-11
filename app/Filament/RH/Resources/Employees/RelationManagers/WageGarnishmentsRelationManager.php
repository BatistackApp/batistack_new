<?php

namespace App\Filament\RH\Resources\Employees\RelationManagers;

use App\Models\RH\WageGarnishment;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class WageGarnishmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'wageGarnishments';

    protected static ?string $title = 'Saisies sur salaires (SATD)';

    protected static string|BackedEnum|null $icon = Phosphor::Bank;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('reference')
                    ->label('Référence SATD')
                    ->required(),
                TextInput::make('total_amount_due')
                    ->label('Montant total dû (€)')
                    ->numeric()
                    ->required(),
                TextInput::make('amount_collected')
                    ->label('Montant déjà prélevé (€)')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('monthly_deduction')
                    ->label('Déduction mensuelle forcée (€)')
                    ->numeric()
                    ->helperText('Laisser vide pour un calcul automatique selon le barème légal.')
                    ->nullable(),
                DatePicker::make('start_date')
                    ->label('Date de réception')
                    ->required()
                    ->native(false),
                Toggle::make('is_active')
                    ->label('Saisie active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')->label('Référence')->searchable(),
                TextColumn::make('total_amount_due')->label('Total dû')->money('EUR'),
                TextColumn::make('amount_collected')->label('Prélevé')->money('EUR'),
                TextColumn::make('monthly_deduction')->label('Mensualité')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . ' € (Forcé)' : 'Auto'),
                TextColumn::make('start_date')->label('Date')->date('d/m/Y'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('Nouveau SATD'),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
