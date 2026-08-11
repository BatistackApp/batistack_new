<?php

namespace App\Filament\RH\Resources\PayrollExports\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PayrollExportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('month')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('year')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')->label('Statut')
                    ->badge()
                    ->searchable(),
                TextColumn::make('total_employees')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Mis à jour le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('export_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (\App\Models\RH\PayrollExport $record) {
                        $csv = "Employé,Heures Base,Heures Réelles,Heures Sup,Jours Abs,Indemnités Déplacement,Total Notes de Frais,Salaire Brut Estimé\n";
                        foreach ($record->variables as $variable) {
                            $name = $variable->employee->first_name . ' ' . $variable->employee->last_name;
                            $csv .= "{$name},{$variable->base_hours},{$variable->worked_hours},{$variable->overtime_hours},{$variable->absence_days},{$variable->travel_allowances},{$variable->expense_reports_total},{$variable->estimated_gross_salary}\n";
                        }

                        $record->update(['status' => \App\Enums\RH\PayrollExportStatus::EXPORTED]);

                        return response()->streamDownload(function () use ($csv) {
                            echo $csv;
                        }, "export_paie_{$record->month}_{$record->year}.csv");
                    }),
            ])
            ->headerActions([
                Action::make('generate')
                    ->label('Générer variables du mois')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        \Filament\Forms\Components\Select::make('month')
                            ->label('Mois')
                            ->options([
                                1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                                5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                                9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
                            ])
                            ->default(now()->month)
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('year')
                            ->label('Année')
                            ->numeric()
                            ->default(now()->year)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $service = new \App\Services\RH\PayrollGenerationService();
                        $service->generate($data['month'], $data['year']);
                        \Filament\Notifications\Notification::make()->success()->title('Variables générées')->send();
                    })
            ])
            ->groupedBulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
