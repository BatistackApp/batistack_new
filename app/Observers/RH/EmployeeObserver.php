<?php

namespace App\Observers\RH;

use App\Models\RH\Employee;
use App\Models\User;
use App\Notifications\RH\WelcomeEmployeeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeObserver
{
    public function created(Employee $employee): void
    {
        if ($employee->email && ! $employee->user_id) {
            // Création du compte utilisateur
            $user = User::create([
                'name' => "{$employee->first_name} {$employee->last_name}",
                'email' => $employee->email,
                'password' => Hash::make(Str::random(12)), // Mot de passe aléatoire (à réinitialiser par mail)
                'email_verified_at' => now(),
                'is_employee' => true,
                'is_admin' => false,
            ]);

            // Liaison de l'employé au user sans déclencher à nouveau l'observer
            $employee->updateQuietly(['user_id' => $user->id]);

            $user->notify(new WelcomeEmployeeNotification);
        }
    }

    public function updated(Employee $employee): void
    {
        if ($employee->isDirty('email') && $employee->user) {
            $employee->user->updateQuietly(['email' => $employee->email]);
        }
    }
}
