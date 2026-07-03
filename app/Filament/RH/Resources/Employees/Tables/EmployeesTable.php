<?php

namespace App\Filament\RH\Resources\Employees\Tables;

use App\Models\RH\Employee;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')
                    ->label('')
                    ->collection('avatar')
                    ->circular(),

                TextColumn::make('registration_number')
                    ->label('Matricule')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                TextColumn::make('full_name')
                    ->label('Nom Complet')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['last_name']),

                TextColumn::make('currentContract.job_title')
                    ->label('Poste Actuel')
                    ->placeholder('Aucun contrat actif')
                    ->description(fn (Employee $record) => $record->currentContract?->type?->getLabel()),

                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_active')
                    ->label('Actif')
                    ->onColor('success')
                    ->offColor('danger'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Filtrer par statut')
                    ->placeholder('Tous les salariés')
                    ->trueLabel('Salariés actifs')
                    ->falseLabel('Salariés sortis'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('proforma')
                        ->label('Paie Pro Forma')
                        ->icon('heroicon-o-document-currency-euro')
                        ->form([
                            \Filament\Forms\Components\Select::make('month')
                                ->label('Mois')
                                ->options([
                                    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                                    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                                    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
                                ])
                                ->default(now()->subMonth()->month)
                                ->required(),
                            \Filament\Forms\Components\Select::make('year')
                                ->label('Année')
                                ->options(array_combine(range(now()->year - 2, now()->year), range(now()->year - 2, now()->year)))
                                ->default(now()->year)
                                ->required(),
                        ])
                        ->action(function (Employee $record, array $data, \App\Services\RH\RHDocumentService $service) {
                            $path = $service->generateProFormaPayslip($record, $data['month'], $data['year']);
                            return \Illuminate\Support\Facades\Storage::disk('public')->download($path);
                        }),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
