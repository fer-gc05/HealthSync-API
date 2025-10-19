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

        // Seed doctor availability
        $this->call(DoctorAvailabilitySeeder::class);

        // Seed medical records (depends on medical staff)
        $this->call(MedicalRecordSeeder::class);

        // Assign roles to users
        $this->assignRolesToUsers();
    }

    private function assignRolesToUsers(): void
    {
        // Assign patient role to patient users
        $patientRole = \Spatie\Permission\Models\Role::where('name', 'patient')->first();
        if ($patientRole) {
            \App\Models\User::whereHas('patient')->get()->each(function ($user) use ($patientRole) {
                if (!$user->hasRole('patient')) {
                    $user->assignRole($patientRole);
                }
            });
        }

        // Assign doctor role to medical staff users
        $doctorRole = \Spatie\Permission\Models\Role::where('name', 'doctor')->first();
        if ($doctorRole) {
            \App\Models\User::whereHas('medicalStaff')->get()->each(function ($user) use ($doctorRole) {
                if (!$user->hasRole('doctor')) {
                    $user->assignRole($doctorRole);
                }
            });
        }

        $this->command->info('Roles assigned to users successfully!');
    }
}
