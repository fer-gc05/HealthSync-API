<?php

namespace Database\Seeders;

use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\MedicalStaff;
use App\Models\Appointment;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class MedicalRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patients = Patient::all();
        $doctors = MedicalStaff::all();

        if ($patients->isEmpty() || $doctors->isEmpty()) {
            $this->command->info('No hay pacientes o doctores disponibles para crear registros médicos.');
            return;
        }

        // Tipos de registros médicos
        $recordTypes = [
            'consulta_general',
            'control_rutinario',
            'diagnostico',
            'tratamiento',
            'seguimiento',
            'emergencia',
            'revision',
            'evaluacion'
        ];

        // Diagnósticos comunes
        $diagnoses = [
            'Hipertensión arterial',
            'Diabetes tipo 2',
            'Gripe común',
            'Dermatitis',
            'Gastritis',
            'Migraña',
            'Ansiedad',
            'Depresión',
            'Artritis',
            'Bronquitis',
            'Sinusitis',
            'Conjuntivitis',
            'Dolor lumbar',
            'Insomnio',
            'Reflujo gastroesofágico',
            'Anemia',
            'Hipercolesterolemia',
            'Osteoporosis',
            'Asma',
            'Alergia estacional'
        ];

        // Tratamientos comunes
        $treatments = [
            'Medicamento prescrito',
            'Reposo',
            'Dieta especial',
            'Ejercicio físico',
            'Terapia psicológica',
            'Cirugía menor',
            'Fisioterapia',
            'Control de seguimiento',
            'Cambio de medicación',
            'Terapia de grupo'
        ];

        // Síntomas comunes
        $symptoms = [
            'Dolor de cabeza',
            'Fiebre',
            'Tos',
            'Dolor abdominal',
            'Náuseas',
            'Fatiga',
            'Dolor en las articulaciones',
            'Dificultad para respirar',
            'Dolor de pecho',
            'Mareos',
            'Dolor de espalda',
            'Insomnio',
            'Ansiedad',
            'Depresión',
            'Pérdida de apetito',
            'Náuseas',
            'Vómitos',
            'Diarrea',
            'Estreñimiento',
            'Pérdida de peso'
        ];

        // Crear registros médicos históricos
        for ($i = 0; $i < 50; $i++) {
            $patient = $patients->random();
            $doctor = $doctors->random();
            $recordDate = Carbon::now()->subDays(rand(30, 365));

            MedicalRecord::firstOrCreate([
                'patient_id' => $patient->id,
                'medical_staff_id' => $doctor->id,
                'created_at' => $recordDate,
            ], [
                'appointment_id' => null,
                'subjective' => "Historia clínica detallada del paciente. Síntoma: " . $symptoms[array_rand($symptoms)],
                'objective' => "Examen físico completo realizado.",
                'assessment' => $diagnoses[array_rand($diagnoses)],
                'plan' => $treatments[array_rand($treatments)],
                'prescriptions' => rand(0, 1) ? "Medicamento prescrito según diagnóstico" : null,
                'recommendations' => "Seguimiento según evolución. Registro médico histórico.",
                'vital_signs' => [
                    'blood_pressure' => rand(100, 140) . '/' . rand(60, 90),
                    'heart_rate' => rand(60, 100),
                    'temperature' => rand(36, 38) . '.' . rand(0, 9),
                    'weight' => rand(50, 100) . ' kg',
                    'height' => rand(150, 190) . ' cm'
                ],
                'updated_at' => $recordDate,
            ]);
        }

        $this->command->info('Registros médicos creados exitosamente!');
    }
}
