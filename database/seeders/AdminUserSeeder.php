<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the admin role
        $adminRole = Role::where('name', 'admin')->first();

        if (!$adminRole) {
            $this->command->error('Admin role not found. Please run RolePermissionSeeder first.');
            return;
        }

        // Create admin users
        $adminUsers = [
            [
                'name' => 'Fernando Gil',
                'email' => 'fernando.gil@saludone.com',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Franco Maidana',
                'email' => 'franco.maidana@saludone.com',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Sebastian Lemus',
                'email' => 'sebastian.lemus@saludone.com',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ],
        ];

        foreach ($adminUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            // Assign role if not already assigned
            if (!$user->hasRole($adminRole)) {
                $user->assignRole($adminRole);
                $this->command->info("Assigned admin role to: {$user->name} ({$user->email})");
            } else {
                $this->command->warn("User already has admin role: {$userData['email']}");
            }
        }

        $this->command->info('Admin users seeded successfully!');
    }
}
