<?php

namespace App\Filament\Interventions\Pages;

use App\Filament\Interventions\InterventionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInterventions extends ListRecords
{
    protected static string $resource = InterventionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('optimizeRoute')
                ->label('Optimiser une tournée')
                ->icon('heroicon-m-map')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\Select::make('technicien_id')
                        ->label('Technicien')
                        ->options(\App\Models\RH\Employee::all()->pluck('full_name', 'id'))
                        ->required()
                        ->searchable(),
                    \Filament\Forms\Components\DatePicker::make('date')->label('Date')
                        ->label('Date')
                        ->required()
                        ->default(now()),
                ])
                ->action(function (array $data) {
                    \App\Jobs\OptimizeTechnicianRouteJob::dispatch(
                        $data['technicien_id'],
                        $data['date'],
                        auth()->id()
                    );
                    
                    \Filament\Notifications\Notification::make()
                        ->info()
                        ->title('Optimisation lancée')
                        ->body('L\'optimisation de la tournée est en cours d\'exécution en arrière-plan. Vous recevrez une notification une fois terminée.')
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
