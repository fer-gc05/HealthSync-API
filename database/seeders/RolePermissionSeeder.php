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
        
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);   // Admin
        $doctorRole = Role::create(['name' => 'doctor']); // Doctor
        $patientRole = Role::create(['name' => 'patient']); // Patient


        // Define permissions
        $permissions = [
            // Admin permissions
            'manage-users',
            'manage-doctors',
            'manage-patients',
            'view-reports',
            'manage-system',

            // Doctor permissions
            'view-patients',
            'create-appointments',
            'update-appointments',
            'view-medical-records',

            // Patient permissions
            'view-own-profile',
            'view-own-appointments',
            'view-own-medical-records'
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign permissions
        $adminRole->givePermissionTo([
            // Admin has all permissions
            'manage-users',
            'manage-doctors',
            'manage-patients',
            'view-reports',
            'manage-system'
        ]);

        $doctorRole->givePermissionTo([
            'view-patients',
            'create-appointments',
            'update-appointments',
            'view-medical-records'
        ]);

        $patientRole->givePermissionTo([
            'view-own-profile',
            'create-appointments',
            'view-own-appointments',
            'view-own-medical-records'
        ]);


        echo "Roles and permissions seeded successfully!";
    }
}
