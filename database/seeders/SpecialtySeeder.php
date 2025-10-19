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
                'description' => 'Atención médica integral para pacientes de todas las edades, incluyendo prevención, diagnóstico y tratamiento de enfermedades comunes',
                'active' => true,
            ],
            [
                'name' => 'Cardiología',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento de enfermedades del corazón y sistema cardiovascular',
                'active' => true,
            ],
            [
                'name' => 'Dermatología',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento de enfermedades de la piel, pelo y uñas',
                'active' => true,
            ],
            [
                'name' => 'Ginecología',
                'description' => 'Especialidad médica que se encarga de la salud reproductiva de la mujer y sistema reproductor femenino',
                'active' => true,
            ],
            [
                'name' => 'Pediatría',
                'description' => 'Especialidad médica que se encarga del cuidado de la salud de los niños desde el nacimiento hasta la adolescencia',
                'active' => true,
            ],
            [
                'name' => 'Psicología',
                'description' => 'Especialidad que se encarga del estudio y tratamiento de los procesos mentales, emocionales y del comportamiento humano',
                'active' => true,
            ],
            [
                'name' => 'Neurología',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento de enfermedades del sistema nervioso central y periférico',
                'active' => true,
            ],
            [
                'name' => 'Oftalmología',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento de enfermedades de los ojos y sistema visual',
                'active' => true,
            ],
            [
                'name' => 'Ortopedia',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento de enfermedades del sistema musculoesquelético',
                'active' => true,
            ],
            [
                'name' => 'Psiquiatría',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento de enfermedades mentales y trastornos psiquiátricos',
                'active' => true,
            ],
            [
                'name' => 'Endocrinología',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento de enfermedades del sistema endocrino y metabolismo',
                'active' => true,
            ],
            [
                'name' => 'Gastroenterología',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento de enfermedades del sistema digestivo',
                'active' => true,
            ],
            [
                'name' => 'Neumología',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento de enfermedades del sistema respiratorio',
                'active' => true,
            ],
            [
                'name' => 'Urología',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento de enfermedades del sistema urinario y reproductor masculino',
                'active' => true,
            ],
            [
                'name' => 'Oncología',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento del cáncer y tumores',
                'active' => true,
            ],
            [
                'name' => 'Reumatología',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento de enfermedades reumáticas y autoinmunes',
                'active' => true,
            ],
            [
                'name' => 'Anestesiología',
                'description' => 'Especialidad médica que se encarga de la administración de anestesia y cuidado perioperatorio',
                'active' => true,
            ],
            [
                'name' => 'Radiología',
                'description' => 'Especialidad médica que se encarga del diagnóstico por imágenes médicas',
                'active' => true,
            ],
            [
                'name' => 'Medicina Interna',
                'description' => 'Especialidad médica que se encarga del diagnóstico y tratamiento de enfermedades internas en adultos',
                'active' => true,
            ],
            [
                'name' => 'Medicina de Emergencias',
                'description' => 'Especialidad médica que se encarga de la atención de urgencias y emergencias médicas',
                'active' => true,
            ],
        ];

        foreach ($specialties as $specialty) {
            Specialty::firstOrCreate(
                ['name' => $specialty['name']],
                $specialty
            );
        }
    }
}
