<?php

namespace App\Livewire\Kiosk;

use App\Models\RH\Employee;
use Livewire\Component;

class BiometricEnrollment extends Component
{
    public $selectedEmployeeId;
    public $employeesList = [];
    public $isEnrolled = false;

    public function mount()
    {
        // Only load active employees with consent who haven't been enrolled yet (or maybe want to re-enroll)
        $this->employeesList = Employee::where('is_active', true)
            ->where('biometric_consent', true)
            ->get()
            ->mapWithKeys(fn ($emp) => [$emp->id => $emp->full_name])
            ->toArray();
    }

    public function enroll($descriptor)
    {
        if (!$this->selectedEmployeeId) {
            return;
        }

        $employee = Employee::find($this->selectedEmployeeId);
        if ($employee) {
            $employee->face_descriptor = $descriptor; // Cast handles array to json
            $employee->save();
            $this->isEnrolled = true;
            
            // Remove from list or keep it
        }
    }

    public function render()
    {
        return view('livewire.kiosk.biometric-enrollment')->layout('layouts.kiosk');
    }
}
