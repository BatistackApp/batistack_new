<?php

namespace App\Filament\Interventions\Pages;

use App\Filament\Interventions\InterventionResource;
use App\Jobs\OptimizeTechnicianRouteJob;
use App\Models\RH\Employee;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListInterventions extends ListRecords
{
    protected static string $resource = InterventionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('optimizeRoute')
                ->label('Optimiser une tournée')
                ->icon('heroicon-m-map')
                ->color('success')
                ->form([
                    Select::make('technicien_id')
                        ->label('Technicien')
                        ->options(Employee::all()->pluck('full_name', 'id'))
                        ->required()
                        ->searchable(),
                    DatePicker::make('date')->label('Date')
                        ->label('Date')
                        ->required()
                        ->default(now()),
                ])
                ->action(function (array $data) {
                    OptimizeTechnicianRouteJob::dispatch(
                        $data['technicien_id'],
                        $data['date'],
                        auth()->id()
                    );

                    Notification::make()
                        ->info()
                        ->title('Optimisation lancée')
                        ->body('L\'optimisation de la tournée est en cours d\'exécution en arrière-plan. Vous recevrez une notification une fois terminée.')
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
