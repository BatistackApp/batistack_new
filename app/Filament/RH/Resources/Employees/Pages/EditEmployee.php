<?php

namespace App\Filament\RH\Resources\Employees\Pages;

use App\Filament\RH\Resources\Employees\EmployeeResource;
use App\Services\RH\EmployeePinService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['pin'])) {
            $data['pin_hash'] = app(EmployeePinService::class)->hashPin($data['pin']);
        }

        unset($data['pin']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
