<?php

namespace App\Filament\RH\Resources\Employees\Pages;

use App\Filament\RH\Resources\Employees\EmployeeResource;
use App\Models\RH\Employee;
use App\Services\RH\RHDocumentService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('print')
                ->label('Imprimer la fiche')
                ->color('gray')
                ->icon(Phosphor::Printer)
                ->action(fn (Employee $record, RHDocumentService $service) => $service->download($service->generateFullRecord($record))),
        ];
    }
}
