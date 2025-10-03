<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Patient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuarios para pacientes
        $patientUsers = [
            [
                'name' => 'Paciente Test 1',
                'email' => 'paciente1@test.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Paciente Test 2',
                'email' => 'paciente2@test.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Paciente Test 3',
                'email' => 'paciente3@test.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Paciente Test 4',
                'email' => 'paciente4@test.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Paciente Test 5',
                'email' => 'paciente5@test.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
        ];

        $genders = ['male', 'female', 'other'];
        $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

        foreach ($patientUsers as $index => $userData) {
            $user = User::create($userData);
            // $user->assignRole('patient');

            // Crear registro de paciente
            Patient::create([
                'user_id' => $user->id,
                'birth_date' => now()->subYears(rand(18, 80))->subDays(rand(0, 365)),
                'gender' => $genders[array_rand($genders)],
                'phone' => '+57' . rand(3000000000, 3999999999),
                'address' => 'Dirección de prueba ' . ($index + 1),
                'blood_type' => $bloodTypes[array_rand($bloodTypes)],
                'allergies' => $index % 3 === 0 ? 'Alergia a la penicilina' : null,
                'current_medications' => $index % 2 === 0 ? 'Medicamento A, Medicamento B' : null,
                'insurance_number' => 'INS-' . str_pad($index + 1, 8, '0', STR_PAD_LEFT),
                'emergency_contact_name' => 'Contacto de Emergencia ' . ($index + 1),
                'emergency_contact_phone' => '+57' . rand(3000000000, 3999999999),
            ]);
        }
    }
}
