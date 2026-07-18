<?php

namespace App\Filament\Paie\Resources\Paie\Payslips\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PayslipsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('period')
                    ->searchable(),
                TextColumn::make('base_hours')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('overtime_hours')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('hourly_rate')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('gross_salary')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('net_social')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('taxable_net')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pas_rate')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pas_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('net_payable')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('net_paid')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('employer_cost')
                    ->money()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('pdf_path')
                    ->searchable(),
                TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
