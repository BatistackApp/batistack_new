<?php

namespace App\Filament\RH\Resources\Employees\Pages;

use App\Filament\RH\Resources\Employees\EmployeeResource;
use App\Services\RH\EmployeePinService;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['pin'])) {
            $data['pin_hash'] = app(EmployeePinService::class)->hashPin($data['pin']);
        }

        unset($data['pin']);

        return $data;
    }
}
