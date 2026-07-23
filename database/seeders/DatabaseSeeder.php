<?php

namespace Database\Seeders;

use App\Models\RH\Contract;
use App\Models\RH\Employee;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        Artisan::call('make:filament-user', [
            '--name' => 'admin',
            '--email' => 'admin@admin.com',
            '--password' => 'admin',
            '--panel' => 'core',
        ]);

        $this->call(CoreSeeder::class);

        Employee::create([
            'user_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'admin@admin.com',
            'registration_number' => 'MAT-'.now()->year.'-1000',
            'birth_date' => '1990-01-01',
            'social_security_number' => '190010100000000',
            'is_active' => true,
            'address' => '1 Rue de la Paix',
            'postal_code' => '75000',
            'city' => 'Paris',
        ]);

        Contract::create([
            'employee_id' => 1,
            'type' => \App\Enums\RH\ContractType::CDI,
            'start_date' => now()->subYear(),
            'job_title' => 'Administrateur',
            'hourly_rate' => 20.00,
            'weekly_hours' => 35.00,
        ]);
    }
}
