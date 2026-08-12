<?php

namespace App\Observers\RH;

use App\Models\RH\Employee;
use App\Models\User;
use App\Notifications\RH\WelcomeEmployeeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Log;

class EmployeeObserver
{
    /**
     * @throws \Exception
     */
    public function creating(Employee $employee): void
    {
        if (empty($employee->uuid)) {
            $employee->uuid = Str::uuid();
        }
        if (empty($employee->registration_number)) {
            throw new \Exception('Registration number required');
        }
        if (Employee::where('registration_number', $employee->registration_number)->exists()) {
            throw new \Exception('Registration number exists');
        }
        if ($employee->email && Employee::where('email', $employee->email)->exists()) {
            throw new \Exception('Email exists');
        }
    }

    public function saving(Employee $employee): void
    {
        $employee->registration_number = strtoupper(trim($employee->registration_number));
        if ($employee->email) {
            $employee->email = strtolower(trim($employee->email));
        }
    }

    public function created(Employee $employee): void
    {
        Log::info('Employee created', ['id' => $employee->id, 'registration' => $employee->registration_number]);
        if ($employee->email && ! $employee->user_id) {
            $user = User::create(['name' => $employee->getFullName(), 'email' => $employee->email, 'password' => Hash::make(Str::random(12)), 'email_verified_at' => now(), 'is_employee' => true]);
            $employee->updateQuietly(['user_id' => $user->id]);
            $user->notify(new WelcomeEmployeeNotification);
        }
    }

    public function updated(Employee $employee): void
    {
        if ($employee->isDirty('email') && $employee->user) {
            $employee->user->updateQuietly(['email' => $employee->email]);
        }

        if ($employee->wasChanged('onboarding_completed') && $employee->onboarding_completed) {
            try {
                $relativePath = app(\App\Services\RH\RHDocumentService::class)->generateAffiliationMutuelle($employee);
                $disk = \App\Services\Core\DocumentService::getDisk();
                $absolutePath = \Illuminate\Support\Facades\Storage::disk($disk)->path($relativePath);

                $employee->addMedia($absolutePath)
                    ->toMediaCollection('rh_documents');
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Impossible de générer le bulletin d'affiliation: " . $e->getMessage());
            }

            try {
                app(\App\Services\Paie\DigiposteService::class)->createOrGetSafe($employee);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Impossible de créer le coffre Digiposte: " . $e->getMessage());
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function deleting(Employee $employee): void
    {
        if ($employee->contracts()->exists()) {
            throw new \Exception('Cannot delete: has contracts');
        }
        if ($employee->timeEntries()->exists()) {
            throw new \Exception('Cannot delete: has time entries');
        }

        \App\Models\Chantiers\ResourceAllocation::where('allocatable_type', Employee::class)
            ->where('allocatable_id', $employee->id)
            ->delete();
    }

    public function deleted(Employee $employee): void
    {
        Log::warning('Employee deleted', ['id' => $employee->id]);
        if ($employee->user) {
            $employee->user->delete();
        }
    }
}
