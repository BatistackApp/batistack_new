<?php

namespace App\Livewire\Kiosk;

use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use Carbon\Carbon;
use Livewire\Component;

class BiometricClock extends Component
{
    public array $employeesData = [];
    public array $recentLogs = [];

    public function mount()
    {
        // Load all active employees with biometric consent and a valid face descriptor
        $employees = Employee::where('is_active', true)
            ->where('biometric_consent', true)
            ->whereNotNull('face_descriptor')
            ->get();

        $this->employeesData = $employees->map(function ($emp) {
            return [
                'id' => $emp->id,
                'name' => $emp->full_name,
                // face_descriptor is cast to array in the model
                'descriptor' => $emp->face_descriptor,
            ];
        })->toArray();
    }

    public function clockIn($employeeId)
    {
        $employee = Employee::find($employeeId);
        if (!$employee) return;

        // Verify if not already clocked in today to avoid spamming
        $todayEntry = TimeEntry::where('employee_id', $employee->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        if (!$todayEntry) {
            // Create a Draft TimeEntry for the day
            TimeEntry::create([
                'employee_id' => $employee->id,
                'chantier_id' => null, // Kiosk usually implies a specific site, but here it's generic
                'date' => now()->toDateString(),
                'hours' => 7.0,
                'travel_hours' => 0,
                'type' => TimeEntryType::NORMAL,
                'status' => TimeEntryStatus::DRAFT,
            ]);

            $message = "Bienvenue, {$employee->first_name}. Présence enregistrée à " . now()->format('H:i');
        } else {
            $message = "Bonjour {$employee->first_name}. Vous avez déjà pointé aujourd'hui.";
        }

        array_unshift($this->recentLogs, [
            'time' => now()->format('H:i:s'),
            'message' => $message,
            'name' => $employee->full_name,
        ]);

        if (count($this->recentLogs) > 5) {
            array_pop($this->recentLogs);
        }
    }

    public function render()
    {
        return view('livewire.kiosk.biometric-clock')->layout('layouts.kiosk');
    }
}
