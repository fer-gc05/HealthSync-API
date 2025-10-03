<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\MedicalStaff;
use App\Models\Specialty;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MedicalStaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuarios para personal médico
        $medicalStaffUsers = [
            [
                'name' => 'Dr. Juan Pérez',
                'email' => 'juan.perez@healthsync.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dra. María García',
                'email' => 'maria.garcia@healthsync.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dr. Carlos López',
                'email' => 'carlos.lopez@healthsync.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dra. Ana Martínez',
                'email' => 'ana.martinez@healthsync.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dr. Roberto Silva',
                'email' => 'roberto.silva@healthsync.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
        ];

        $specialties = Specialty::all();

        foreach ($medicalStaffUsers as $index => $userData) {
            $user = User::create($userData);
            // $user->assignRole('doctor');

            // Crear registro de personal médico
            MedicalStaff::create([
                'user_id' => $user->id,
                'professional_license' => 'LIC-' . str_pad($index + 1, 6, '0', STR_PAD_LEFT),
                'specialty_id' => $specialties->random()->id,
                'subspecialty' => $index % 2 === 0 ? 'Subespecialidad A' : null,
                'active' => true,
                'appointment_duration' => 30,
                'work_schedule' => [
                    'monday' => ['start' => '08:00', 'end' => '17:00'],
                    'tuesday' => ['start' => '08:00', 'end' => '17:00'],
                    'wednesday' => ['start' => '08:00', 'end' => '17:00'],
                    'thursday' => ['start' => '08:00', 'end' => '17:00'],
                    'friday' => ['start' => '08:00', 'end' => '17:00'],
                    'saturday' => null,
                    'sunday' => null,
                ],
            ]);
        }
    }
}
