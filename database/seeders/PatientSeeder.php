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
        // Crear usuarios para pacientes con datos más realistas
        $patientUsers = [
            [
                'name' => 'María González Rodríguez',
                'email' => 'maria.gonzalez@email.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Carlos Alberto Méndez',
                'email' => 'carlos.mendez@email.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Ana Lucía Fernández',
                'email' => 'ana.fernandez@email.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Roberto Silva Torres',
                'email' => 'roberto.silva@email.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Carmen Elena Vargas',
                'email' => 'carmen.vargas@email.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Diego Alejandro Ruiz',
                'email' => 'diego.ruiz@email.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Isabel Cristina Morales',
                'email' => 'isabel.morales@email.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Fernando José Herrera',
                'email' => 'fernando.herrera@email.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Patricia Beatriz Castro',
                'email' => 'patricia.castro@email.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Miguel Ángel Jiménez',
                'email' => 'miguel.jimenez@email.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Sofía Alejandra Ramírez',
                'email' => 'sofia.ramirez@email.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Andrés Felipe López',
                'email' => 'andres.lopez@email.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Valentina Sánchez',
                'email' => 'valentina.sanchez@email.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Jorge Luis Martínez',
                'email' => 'jorge.martinez@email.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Natalia Esperanza Díaz',
                'email' => 'natalia.diaz@email.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
        ];

        $genders = ['male', 'female', 'other'];
        $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

        // Datos más realistas para pacientes
        $addresses = [
            'Calle 85 # 15-32, Bogotá',
            'Carrera 7 # 32-10, Medellín',
            'Calle 50 # 25-15, Cali',
            'Carrera 15 # 93-47, Bogotá',
            'Calle 72 # 11-20, Barranquilla',
            'Carrera 3 # 28-15, Bucaramanga',
            'Calle 100 # 15-30, Bogotá',
            'Carrera 50 # 80-25, Medellín',
            'Calle 30 # 45-12, Cali',
            'Carrera 9 # 75-20, Bogotá',
            'Calle 63 # 8-40, Barranquilla',
            'Carrera 25 # 40-18, Bucaramanga',
            'Calle 127 # 7-15, Bogotá',
            'Carrera 43 # 2-50, Medellín',
            'Calle 5 # 22-30, Cali'
        ];

        $allergies = [
            null,
            'Penicilina',
            'Sulfamidas',
            'Aspirina',
            'Polen',
            'Mariscos',
            'Látex',
            'Penicilina, Sulfamidas',
            'Polen, Ácaros',
            null,
            'Aspirina, Ibuprofeno',
            'Mariscos, Frutos secos',
            'Látex, Polen',
            null,
            'Penicilina, Látex'
        ];

        $medications = [
            null,
            'Metformina 500mg',
            'Losartán 50mg',
            'Atorvastatina 20mg',
            'Omeprazol 20mg',
            'Metformina 500mg, Losartán 50mg',
            'Levotiroxina 75mcg',
            'Amlodipino 5mg',
            'Simvastatina 10mg',
            null,
            'Metformina 850mg, Glibenclamida 5mg',
            'Losartán 100mg, Hidroclorotiazida 25mg',
            'Atorvastatina 40mg, Aspirina 100mg',
            'Omeprazol 40mg, Domperidona 10mg',
            null
        ];

        $emergencyContacts = [
            ['name' => 'Carlos González', 'phone' => '+57' . rand(3000000000, 3999999999)],
            ['name' => 'María Méndez', 'phone' => '+57' . rand(3000000000, 3999999999)],
            ['name' => 'Roberto Fernández', 'phone' => '+57' . rand(3000000000, 3999999999)],
            ['name' => 'Ana Silva', 'phone' => '+57' . rand(3000000000, 3999999999)],
            ['name' => 'Diego Vargas', 'phone' => '+57' . rand(3000000000, 3999999999)],
            ['name' => 'Carmen Ruiz', 'phone' => '+57' . rand(3000000000, 3999999999)],
            ['name' => 'Fernando Morales', 'phone' => '+57' . rand(3000000000, 3999999999)],
            ['name' => 'Patricia Herrera', 'phone' => '+57' . rand(3000000000, 3999999999)],
            ['name' => 'Miguel Castro', 'phone' => '+57' . rand(3000000000, 3999999999)],
            ['name' => 'Sofía Jiménez', 'phone' => '+57' . rand(3000000000, 3999999999)],
            ['name' => 'Andrés Ramírez', 'phone' => '+57' . rand(3000000000, 3999999999)],
            ['name' => 'Valentina López', 'phone' => '+57' . rand(3000000000, 3999999999)],
            ['name' => 'Jorge Sánchez', 'phone' => '+57' . rand(3000000000, 3999999999)],
            ['name' => 'Natalia Martínez', 'phone' => '+57' . rand(3000000000, 3999999999)],
            ['name' => 'Luis Díaz', 'phone' => '+57' . rand(3000000000, 3999999999)]
        ];

        foreach ($patientUsers as $index => $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
            // $user->assignRole('patient');

            // Crear registro de paciente con datos más realistas (skip if exists)
            Patient::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'user_id' => $user->id,
                    'birth_date' => now()->subYears(rand(18, 75))->subDays(rand(0, 365)),
                    'gender' => $genders[array_rand($genders)],
                    'phone' => '+57' . rand(3000000000, 3999999999),
                    'address' => $addresses[$index],
                    'blood_type' => $bloodTypes[array_rand($bloodTypes)],
                    'allergies' => $allergies[$index],
                    'current_medications' => $medications[$index],
                    'insurance_number' => 'EPS-' . str_pad($index + 1, 8, '0', STR_PAD_LEFT),
                    'emergency_contact_name' => $emergencyContacts[$index]['name'],
                    'emergency_contact_phone' => $emergencyContacts[$index]['phone'],
                ]
            );
        }
    }
}
