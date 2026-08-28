<?php

namespace Database\Seeders;

use App\Enums\RH\ContractType;
use App\Models\RH\Contract;
use App\Models\RH\Employee;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Database\Seeders\Accounting\PcgSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

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

        $this->call(ShieldSeeder::class);
        $this->call(PcgSeeder::class);

        $admin = User::where('email', 'admin@admin.com')->first();
        if ($admin) {
            Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
            $admin->assignRole('super_admin');
        }

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
            'type' => ContractType::CDI,
            'start_date' => now()->subYear(),
            'job_title' => 'Administrateur',
            'hourly_rate' => 20.00,
            'weekly_hours' => 35.00,
        ]);

        // Seed demo data if DEMO_SEED=true
        if (env('DEMO_SEED', false)) {
            $this->call(DemoSeeder::class);
        }
    }
}
