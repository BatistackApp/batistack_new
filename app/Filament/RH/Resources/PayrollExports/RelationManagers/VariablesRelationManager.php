<?php

namespace App\Filament\RH\Resources\PayrollExports\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariablesRelationManager extends RelationManager
{
    protected static string $relationship = 'variables';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->relationship('employee', 'id')
                    ->required(),
                TextInput::make('base_hours')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('worked_hours')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('overtime_hours')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('absence_days')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('travel_allowances')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('expense_reports_total')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('estimated_gross_salary')
                    ->required()
                    ->numeric()
                    ->default(0.0),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employee.id')
                    ->label('Employee'),
                TextEntry::make('base_hours')
                    ->numeric(),
                TextEntry::make('worked_hours')
                    ->numeric(),
                TextEntry::make('overtime_hours')
                    ->numeric(),
                TextEntry::make('absence_days')
                    ->numeric(),
                TextEntry::make('travel_allowances')
                    ->numeric(),
                TextEntry::make('expense_reports_total')
                    ->numeric(),
                TextEntry::make('estimated_gross_salary')
                    ->numeric(),
                TextEntry::make('created_at')->label('Créé le')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')->label('Mis à jour le')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('employee.first_name')
                    ->label('Employé')
                    ->formatStateUsing(fn ($record) => $record->employee->first_name . ' ' . $record->employee->last_name)
                    ->sortable(),
                TextColumn::make('base_hours')
                    ->label('H. Base')
                    ->numeric(),
                TextColumn::make('worked_hours')
                    ->label('H. Réelles')
                    ->numeric(),
                TextColumn::make('overtime_hours')
                    ->label('H. Sup')
                    ->numeric()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
                TextColumn::make('absence_days')
                    ->label('Absences (J)')
                    ->numeric()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('travel_allowances')
                    ->label('Indemnités')
                    ->money('EUR'),
                TextColumn::make('expense_reports_total')
                    ->label('Notes Frais')
                    ->money('EUR'),
                TextColumn::make('estimated_gross_salary')
                    ->label('Salaire Brut Est.')
                    ->money('EUR')
                    ->weight('bold'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
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
