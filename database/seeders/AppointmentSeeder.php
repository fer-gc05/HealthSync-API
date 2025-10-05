<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\MedicalStaff;
use Carbon\Carbon;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patients = Patient::all();
        $doctors = MedicalStaff::all();

        if ($patients->isEmpty() || $doctors->isEmpty()) {
            $this->command->info('No hay pacientes o doctores disponibles para crear citas.');
            return;
        }

        // Crear 10 citas de prueba
        for ($i = 0; $i < 10; $i++) {
            $startDate = Carbon::now()->addDays(rand(-30, 30))->addHours(rand(8, 18));
            $endDate = $startDate->copy()->addMinutes(30);
            
            Appointment::create([
                'patient_id' => $patients->random()->id,
                'medical_staff_id' => $doctors->random()->id,
                'specialty_id' => 1, // Asumiendo que existe la especialidad con ID 1
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => ['presencial', 'virtual'][rand(0, 1)],
                'status' => ['programada', 'confirmada', 'completada', 'cancelada'][rand(0, 3)],
                'reason' => 'Cita de prueba ' . ($i + 1),
                'urgent' => rand(0, 1) == 1,
                'priority' => rand(1, 5),
            ]);
        }

        $this->command->info('Citas de prueba creadas exitosamente!');
    }
}
