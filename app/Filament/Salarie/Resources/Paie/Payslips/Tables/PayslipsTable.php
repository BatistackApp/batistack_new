<?php

namespace App\Filament\Salarie\Resources\Paie\Payslips\Tables;

use App\Models\Paie\Payslip;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class PayslipsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period')
                    ->label('Période')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('net_paid')
                    ->label('Net Payé')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('payment_date')
                    ->label('Date de paiement')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('download')
                    ->label('Télécharger')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Payslip $record) => $record->pdf_path ? Storage::url($record->pdf_path) : null)
                    ->openUrlInNewTab()
                    ->visible(fn (Payslip $record) => ! empty($record->pdf_path)),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
