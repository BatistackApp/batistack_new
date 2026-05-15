<?php

namespace Database\Seeders;

use App\Enums\RH\ContractType;
use App\Enums\RH\QualificationType;
use App\Models\RH\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RHSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'email' => 'jean.dupont@batistack.test',
            'password' => Hash::make('password'),
            'name' => 'Jean Dupont',
            'is_employee' => true,
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        // 1. Création d'un employé type (Couvreur)
        $employee = Employee::create([
            'registration_number' => 'MAT-2025-001',
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean.dupont@batistack.test',
            'phone' => '0601020304',
            'birth_date' => '1985-06-15',
            'is_active' => true,
            'user_id' => $user->id,
            'uuid' => Str::uuid(),
            'address' => fake()->streetAddress(),
            'postal_code' => fake()->postcode,
            'city' => fake()->city,
        ]);

        // 2. Création de son contrat
        $employee->contracts()->create([
            'type' => ContractType::CDI,
            'start_date' => '2020-01-01',
            'job_title' => 'Couvreur Zingueur Qualifié',
            'hourly_rate' => 14.5000,
            'weekly_hours' => 35.00,
        ]);

        // 3. Ajout d'habilitations (Sécurité & CACES)
        $employee->qualifications()->createMany([
            [
                'type' => QualificationType::CACES,
                'label' => 'CACES R482 - Catégorie F (Chariot télescopique)',
                'obtained_at' => '2023-02-10',
                'expires_at' => '2028-02-10',
            ],
            [
                'type' => QualificationType::SAFETY,
                'label' => 'SST - Sauveteur Secouriste du Travail',
                'obtained_at' => '2024-01-01',
                'expires_at' => '2026-01-01',
            ],
        ]);
    }
}
