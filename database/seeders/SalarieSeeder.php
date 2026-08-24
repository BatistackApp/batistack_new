<?php

namespace Database\Seeders;

use App\Enums\Paie\AdvancePaymentStatus;
use App\Enums\Paie\AdvancePaymentType;
use App\Enums\RH\AbsenceType;
use App\Models\Paie\AdvancePayment;
use App\Models\RH\Abscence;
use App\Models\RH\Employee;
use Illuminate\Database\Seeder;

class SalarieSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::all();
        if ($employees->isEmpty()) {
            return;
        }

        $absenceTypes = [AbsenceType::PAID_LEAVE, AbsenceType::SICK_LEAVE, AbsenceType::UNPAID_LEAVE];
        $paymentStatuses = [AdvancePaymentStatus::PENDING, AdvancePaymentStatus::APPROVED, AdvancePaymentStatus::PAID];
        $paymentTypes = [AdvancePaymentType::CLASSIC, AdvancePaymentType::GRAND_DEPLACEMENT];

        foreach ($employees as $employee) {
            $absenceCount = rand(0, 3);
            for ($a = 0; $a < $absenceCount; $a++) {
                Abscence::create([
                    'employee_id' => $employee->id,
                    'type' => $absenceTypes[array_rand($absenceTypes)],
                    'start_date' => now()->subDays(rand(7, 90)),
                    'end_date' => now()->subDays(rand(1, 6)),
                    'reason' => 'Absence déclarée via le portail salarié',
                    'is_paid' => rand(0, 100) > 50,
                ]);
            }

            if (rand(0, 100) > 70) {
                AdvancePayment::create([
                    'employee_id' => $employee->id,
                    'amount' => rand(100, 500) / 100,
                    'request_date' => now()->subDays(rand(1, 30)),
                    'type' => $paymentTypes[array_rand($paymentTypes)],
                    'status' => $paymentStatuses[array_rand($paymentStatuses)],
                ]);
            }
        }
    }
}
