<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Create roles (skip if already exists)
        $adminRole = Role::firstOrCreate(['name' => 'admin']);   // Admin
        $doctorRole = Role::firstOrCreate(['name' => 'doctor']); // Doctor
        $patientRole = Role::firstOrCreate(['name' => 'patient']); // Patient


        // Define permissions
        $permissions = [
            // Admin permissions
            'manage-users',
            'manage-doctors',
            'manage-patients',
            'manage-specialties',
            'manage-appointments',
            'manage-medical-records',
            'view-reports',
            'view-analytics',
            'manage-system',
            'manage-notifications',
            'view-all-appointments',
            'cancel-any-appointment',
            'assign-appointments',

            // Doctor permissions
            'view-patients',
            'view-patient-details',
            'create-appointments',
            'update-appointments',
            'cancel-appointments',
            'view-medical-records',
            'create-medical-records',
            'update-medical-records',
            'view-own-appointments',
            'manage-availability',
            'view-patient-history',
            'prescribe-medications',
            'request-tests',

            // Patient permissions
            'view-own-profile',
            'update-own-profile',
            'view-own-appointments',
            'create-appointments',
            'cancel-own-appointments',
            'view-own-medical-records',
            'view-own-prescriptions',
            'view-own-test-results',
            'send-messages',
            'view-notifications'
        ];

        // Create permissions (skip if already exists)
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions (sync to avoid duplicates)
        $adminPermissions = [
            'manage-users',
            'manage-doctors',
            'manage-patients',
            'manage-specialties',
            'manage-appointments',
            'manage-medical-records',
            'view-reports',
            'view-analytics',
            'manage-system',
            'manage-notifications',
            'view-all-appointments',
            'cancel-any-appointment',
            'assign-appointments'
        ];

        $doctorPermissions = [
            'view-patients',
            'view-patient-details',
            'create-appointments',
            'update-appointments',
            'cancel-appointments',
            'view-medical-records',
            'create-medical-records',
            'update-medical-records',
            'view-own-appointments',
            'manage-availability',
            'view-patient-history',
            'prescribe-medications',
            'request-tests'
        ];

        $patientPermissions = [
            'view-own-profile',
            'update-own-profile',
            'view-own-appointments',
            'create-appointments',
            'cancel-own-appointments',
            'view-own-medical-records',
            'view-own-prescriptions',
            'view-own-test-results',
            'send-messages',
            'view-notifications'
        ];

        // Sync permissions (removes old ones and adds new ones)
        $adminRole->syncPermissions($adminPermissions);
        $doctorRole->syncPermissions($doctorPermissions);
        $patientRole->syncPermissions($patientPermissions);


        echo "Roles and permissions seeded successfully!";
    }
}
