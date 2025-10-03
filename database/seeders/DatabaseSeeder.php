<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // First seed roles and permissions
        $this->call(RolePermissionSeeder::class);

        // Seed specialties first (no dependencies)
        $this->call(SpecialtySeeder::class);

        // Seed medical staff (depends on specialties)
        $this->call(MedicalStaffSeeder::class);

        // Seed patients (no dependencies on other medical tables)
        $this->call(PatientSeeder::class);

        // Create admin users
        $this->call(AdminUserSeeder::class);

        // Create a test admin user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
