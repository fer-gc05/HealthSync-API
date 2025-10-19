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
        // Crear usuarios para personal médico con más diversidad
        $medicalStaffUsers = [
            [
                'name' => 'Dr. Juan Carlos Pérez',
                'email' => 'juan.perez@saludone.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dra. María Elena García',
                'email' => 'maria.garcia@saludone.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dr. Carlos Alberto López',
                'email' => 'carlos.lopez@saludone.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dra. Ana Lucía Martínez',
                'email' => 'ana.martinez@saludone.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dr. Roberto Fernando Silva',
                'email' => 'roberto.silva@saludone.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dra. Carmen Beatriz Vargas',
                'email' => 'carmen.vargas@saludone.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dr. Diego Alejandro Ruiz',
                'email' => 'diego.ruiz@saludone.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dra. Isabel Cristina Morales',
                'email' => 'isabel.morales@saludone.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dr. Fernando José Herrera',
                'email' => 'fernando.herrera@saludone.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dra. Patricia Beatriz Castro',
                'email' => 'patricia.castro@saludone.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dr. Miguel Ángel Jiménez',
                'email' => 'miguel.jimenez@saludone.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Dra. Sofía Alejandra Ramírez',
                'email' => 'sofia.ramirez@saludone.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
        ];

        $specialties = Specialty::all();

        // Asignar especialidades específicas a cada doctor
        $specialtyAssignments = [
            1,  // Medicina General
            2,  // Cardiología
            3,  // Dermatología
            4,  // Ginecología
            5,  // Pediatría
            6,  // Psicología
            7,  // Neurología
            8,  // Oftalmología
            9,  // Ortopedia
            10, // Psiquiatría
            11, // Endocrinología
            12, // Gastroenterología
        ];

        $subspecialties = [
            null,
            'Cardiología Intervencionista',
            'Dermatología Pediátrica',
            'Ginecología Oncológica',
            'Neonatología',
            'Psicología Clínica',
            'Neurología Pediátrica',
            'Retina y Vítreo',
            'Traumatología',
            'Psiquiatría Infantil',
            'Diabetes y Metabolismo',
            'Endoscopia Digestiva'
        ];

        $appointmentDurations = [30, 45, 60, 30, 30, 50, 45, 30, 45, 50, 30, 45];

        foreach ($medicalStaffUsers as $index => $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
            // $user->assignRole('doctor');

            // Crear registro de personal médico con datos más realistas (skip if exists)
            MedicalStaff::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'user_id' => $user->id,
                    'professional_license' => 'LIC-' . str_pad($index + 1, 6, '0', STR_PAD_LEFT),
                    'specialty_id' => $specialtyAssignments[$index] ?? $specialties->random()->id,
                    'subspecialty' => $subspecialties[$index] ?? null,
                    'active' => true,
                    'appointment_duration' => $appointmentDurations[$index] ?? 30,
                    'work_schedule' => [
                        'monday' => ['start' => '08:00', 'end' => '17:00'],
                        'tuesday' => ['start' => '08:00', 'end' => '17:00'],
                        'wednesday' => ['start' => '08:00', 'end' => '17:00'],
                        'thursday' => ['start' => '08:00', 'end' => '17:00'],
                        'friday' => ['start' => '08:00', 'end' => '17:00'],
                        'saturday' => $index % 3 === 0 ? ['start' => '09:00', 'end' => '13:00'] : null,
                        'sunday' => null,
                    ],
                ]
            );
        }
    }
}
