<?php

namespace Database\Seeders;

use App\Models\Specialty;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecialtySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specialties = [
            [
                'name' => 'Medicina General',
                'description' => 'Atención médica integral para pacientes de todas las edades',
                'active' => true,
            ],
            [
                'name' => 'Cardiología',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento de enfermedades del corazón',
                'active' => true,
            ],
            [
                'name' => 'Dermatología',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento de enfermedades de la piel',
                'active' => true,
            ],
            [
                'name' => 'Ginecología',
                'description' => 'Especialidad médica que se encarga de la salud reproductiva de la mujer',
                'active' => true,
            ],
            [
                'name' => 'Pediatría',
                'description' => 'Especialidad médica que se encarga del cuidado de la salud de los niños',
                'active' => true,
            ],
            [
                'name' => 'Psicología',
                'description' => 'Especialidad que se encarga del estudio y tratamiento de los procesos mentales y del comportamiento',
                'active' => true,
            ],
            [
                'name' => 'Neurología',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento de enfermedades del sistema nervioso',
                'active' => true,
            ],
            [
                'name' => 'Oftalmología',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento de enfermedades de los ojos',
                'active' => true,
            ],
            [
                'name' => 'Ortopedia',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento de enfermedades del sistema musculoesquelético',
                'active' => true,
            ],
            [
                'name' => 'Psiquiatría',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento de enfermedades mentales',
                'active' => true,
            ],
        ];

        foreach ($specialties as $specialty) {
            Specialty::create($specialty);
        }
    }
}
