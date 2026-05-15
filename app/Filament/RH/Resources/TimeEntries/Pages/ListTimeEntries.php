<?php

namespace App\Filament\RH\Resources\TimeEntries\Pages;

use App\Filament\RH\Resources\TimeEntries\TimeEntryResource;
use App\Models\RH\Employee;
use App\Services\RH\RHDocumentService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ListTimeEntries extends ListRecords
{
    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('printMonthlyReport')
                ->label('Imprimer Relevé Mensuel')
                ->icon(Phosphor::Printer)
                ->color('info')
                ->modalWidth('md')
                ->schema([
                    Select::make('employee_id')
                        ->label('Salarié')
                        ->options(Employee::where('is_active', true)->pluck('last_name', 'id'))
                        ->required()
                        ->searchable(),
                    Select::make('month')
                        ->label('Mois')
                        ->options([
                            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
                        ])
                        ->default(now()->month)
                        ->required(),
                    Select::make('year')
                        ->label('Année')
                        ->options(array_combine(range(now()->year, now()->year - 2), range(now()->year, now()->year - 2)))
                        ->default(now()->year)
                        ->required(),
                ])
                ->action(function (array $data, RHDocumentService $service) {
                    $employee = Employee::findOrFail($data['employee_id']);
                    $path = $service->generateMonthlyTimesheet($employee, $data['month'], $data['year']);

                    return response()->download($path);
                }),
        ];
    }
}
