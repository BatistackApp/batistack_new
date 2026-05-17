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

        Employee::factory()->create([
            'user_id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'admin@admin.com',
        ]);

        Contract::factory()->create([
            'employee_id' => 1
        ]);
    }
}
