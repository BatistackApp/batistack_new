<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Core\Company;
use App\Models\RH\Employee;

class PublicSafetyPassportController extends Controller
{
    public function __invoke(string $uuid)
    {
        $employee = Employee::where('uuid', $uuid)
            ->with(['qualifications', 'medicalVisits'])
            ->firstOrFail();

        $company = Company::first();

        return view('public.rh.safety_passport', [
            'employee' => $employee,
            'company' => $company,
            'lastMedicalVisit' => $employee->medicalVisits()->latest('visit_date')->first(),
            'activeQualifications' => $employee->qualifications()
                ->where('expires_at', '>', now())
                ->get(),
        ]);
    }
}
