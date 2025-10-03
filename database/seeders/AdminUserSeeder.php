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
                'email' => 'fernando.gil@healthsync.com',
                'password' => Hash::make('admin123'),
            ],
            [
                'name' => 'Franco Maidana',
                'email' => 'franco.maidana@healthsync.com',
                'password' => Hash::make('admin123'),
            ],
            [
                'name' => 'Sebastian Lemus',
                'email' => 'sebastian.lemus@healthsync.com',
                'password' => Hash::make('admin123'),
            ],
        ];

        foreach ($adminUsers as $userData) {
            // Check if user already exists
            $existingUser = User::where('email', $userData['email'])->first();
            
            if (!$existingUser) {
                $user = User::create($userData);
                $user->assignRole($adminRole);
                
                $this->command->info("Created admin user: {$user->name} ({$user->email})");
            } else {
                $this->command->warn("User already exists: {$userData['email']}");
            }
        }

        $this->command->info('Admin users seeded successfully!');
    }
}
