<?php

namespace App\Filament\Paie\Resources\Paie\Payslips\Pages;

use App\Enums\Paie\PayslipStatus;
use App\Filament\Paie\Resources\Paie\Payslips\PayslipResource;
use App\Models\RH\Employee;
use App\Services\Paie\PayrollCalculationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePayslip extends CreateRecord
{
    protected static string $resource = PayslipResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $employee = Employee::find($data['employee_id']);

        $service = app(PayrollCalculationService::class);
        $payslip = $service->calculateForEmployee(
            $employee,
            $data['period'],
            $data['base_hours'],
            $data['hourly_rate'],
            $data['custom_bonuses'] ?? []
        );

        // Mettre à jour le statut s'il a été spécifié dans le formulaire
        $payslip->update(['status' => $data['status'] ?? PayslipStatus::DRAFT]);

        return $payslip;
    }
}
