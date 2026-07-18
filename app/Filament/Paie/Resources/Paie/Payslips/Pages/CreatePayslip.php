<?php

namespace App\Filament\Paie\Resources\Paie\Payslips\Pages;

use App\Filament\Paie\Resources\Paie\Payslips\PayslipResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayslip extends CreateRecord
{
    protected static string $resource = PayslipResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $employee = \App\Models\RH\Employee::find($data['employee_id']);
        
        $service = app(\App\Services\Paie\PayrollCalculationService::class);
        $payslip = $service->calculateForEmployee(
            $employee,
            $data['period'],
            $data['base_hours'],
            $data['hourly_rate']
        );
        
        // Mettre à jour le statut s'il a été spécifié dans le formulaire
        $payslip->update(['status' => $data['status']]);
        
        return $payslip;
    }
}
