<?php

namespace App\Filament\Paie\Resources\Paie\Payslips;

use App\Filament\Paie\Resources\Paie\Payslips\Pages\CreatePayslip;
use App\Filament\Paie\Resources\Paie\Payslips\Pages\EditPayslip;
use App\Filament\Paie\Resources\Paie\Payslips\Pages\ListPayslips;
use App\Filament\Paie\Resources\Paie\Payslips\Schemas\PayslipForm;
use App\Filament\Paie\Resources\Paie\Payslips\Tables\PayslipsTable;
use App\Models\Paie\Payslip;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Filament\Support\Icons\Heroicon;

class PayslipResource extends Resource
{
    protected static ?string $model = Payslip::class;

    protected static ?string $modelLabel = 'Fiche de paie';

    protected static ?string $pluralModelLabel = 'Fiches de paie';

    protected static string|null|\UnitEnum $navigationGroup = 'Gestion de la Paie';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('employee_id')
                    ->label('Employé')
                    ->relationship('employee', 'last_name')
                    ->required(),
                Forms\Components\TextInput::make('period')
                    ->label('Période (YYYY-MM)')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('base_hours')
                    ->label('Heures de base')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('hourly_rate')
                    ->label('Taux horaire')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('status')
                    ->options(\App\Enums\Paie\PayslipStatus::class)
                    ->required()
                    ->default(\App\Enums\Paie\PayslipStatus::DRAFT),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.last_name')
                    ->label('Employé')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('period')
                    ->label('Période')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gross_salary')
                    ->label('Brut')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_paid')
                    ->label('Net Payé')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (\App\Enums\Paie\PayslipStatus $state): string => match ($state) {
                        \App\Enums\Paie\PayslipStatus::DRAFT => 'gray',
                        \App\Enums\Paie\PayslipStatus::VALIDATED => 'info',
                        \App\Enums\Paie\PayslipStatus::PAID => 'success',
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('generate_pdf')
                    ->label('Générer PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (Payslip $record) {
                        $service = app(\App\Services\Paie\PayslipPdfService::class);
                        $service->generatePdf($record);

                        \Filament\Notifications\Notification::make()
                            ->title('PDF généré avec succès')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('download_pdf')
                    ->label('Télécharger PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Payslip $record) => $record->pdf_path ? Storage::disk('public')->url($record->pdf_path) : null)
                    ->openUrlInNewTab()
                    ->visible(fn (Payslip $record) => $record->pdf_path !== null),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayslips::route('/'),
            'create' => CreatePayslip::route('/create'),
            'edit' => EditPayslip::route('/{record}/edit'),
        ];
    }
}
