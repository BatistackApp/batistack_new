<?php

namespace App\Filament\RH\Resources\Employees\Pages;

use App\Filament\RH\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;
}
