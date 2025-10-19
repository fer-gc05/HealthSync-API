<?php

namespace Database\Seeders;

use App\Models\MedicalStaff;
use App\Models\DoctorAvailability;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DoctorAvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctors = MedicalStaff::all();

        if ($doctors->isEmpty()) {
            $this->command->info('No hay doctores disponibles para crear disponibilidad.');
            return;
        }

        foreach ($doctors as $doctor) {
            // Crear horarios regulares de lunes a viernes con variaciones
            $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

            // Horarios base para cada día
            $baseSchedules = [
                'monday' => ['start' => '08:00:00', 'end' => '17:00:00'],
                'tuesday' => ['start' => '08:00:00', 'end' => '17:00:00'],
                'wednesday' => ['start' => '08:00:00', 'end' => '17:00:00'],
                'thursday' => ['start' => '08:00:00', 'end' => '17:00:00'],
                'friday' => ['start' => '08:00:00', 'end' => '16:00:00'], // Viernes más corto
            ];

            foreach ($weekdays as $day) {
                $schedule = $baseSchedules[$day];

                // Variar horarios según el doctor
                if ($doctor->id % 3 === 0) {
                    // Algunos doctores empiezan más tarde
                    $schedule['start'] = '09:00:00';
                    $schedule['end'] = '18:00:00';
                } elseif ($doctor->id % 3 === 1) {
                    // Algunos doctores tienen horario partido
                    if (in_array($day, ['tuesday', 'thursday'])) {
                        $schedule['end'] = '12:00:00';
                    }
                }

                DoctorAvailability::firstOrCreate([
                    'medical_staff_id' => $doctor->id,
                    'day_of_week' => $day,
                    'start_time' => $schedule['start'],
                    'end_time' => $schedule['end'],
                ], [
                    'is_available' => true,
                    'notes' => "Horario regular de {$day}",
                ]);
            }

            // Crear horarios especiales para algunos doctores
            if ($doctor->id % 2 == 0) {
                // Doctores pares trabajan sábados
                DoctorAvailability::firstOrCreate([
                    'medical_staff_id' => $doctor->id,
                    'day_of_week' => 'saturday',
                    'start_time' => '09:00:00',
                    'end_time' => '13:00:00',
                ], [
                    'is_available' => true,
                    'notes' => 'Horario de sábado',
                ]);
            }

            // Crear horarios específicos para fechas próximas
            $tomorrow = Carbon::tomorrow();
            $nextWeek = Carbon::now()->addWeek();
            $nextMonth = Carbon::now()->addMonth();

            // Horario específico para mañana
            DoctorAvailability::firstOrCreate([
                'medical_staff_id' => $doctor->id,
                'day_of_week' => strtolower($tomorrow->format('l')),
                'specific_date' => $tomorrow->format('Y-m-d'),
            ], [
                'start_time' => '09:00:00',
                'end_time' => '16:00:00',
                'is_available' => true,
                'notes' => 'Horario específico para mañana',
            ]);

            // Horario específico para la próxima semana
            DoctorAvailability::firstOrCreate([
                'medical_staff_id' => $doctor->id,
                'day_of_week' => strtolower($nextWeek->format('l')),
                'specific_date' => $nextWeek->format('Y-m-d'),
            ], [
                'start_time' => '08:30:00',
                'end_time' => '17:30:00',
                'is_available' => true,
                'notes' => 'Horario extendido para la próxima semana',
            ]);

            // Crear algunos días de vacaciones o indisponibilidad
            if ($doctor->id % 4 === 0) {
                $vacationStart = Carbon::now()->addDays(rand(10, 20));
                $vacationEnd = $vacationStart->copy()->addDays(rand(3, 7));

                DoctorAvailability::firstOrCreate([
                    'medical_staff_id' => $doctor->id,
                    'day_of_week' => strtolower($vacationStart->format('l')),
                    'specific_date' => $vacationStart->format('Y-m-d'),
                ], [
                    'start_time' => '00:00:00',
                    'end_time' => '23:59:59',
                    'is_available' => false,
                    'notes' => 'Vacaciones - No disponible',
                ]);
            }

            // Crear horarios de emergencia para algunos doctores
            if ($doctor->id % 5 === 0) {
                $emergencyDays = ['sunday'];
                foreach ($emergencyDays as $day) {
                    DoctorAvailability::firstOrCreate([
                        'medical_staff_id' => $doctor->id,
                        'day_of_week' => $day,
                        'start_time' => '08:00:00',
                        'end_time' => '12:00:00',
                    ], [
                        'is_available' => true,
                        'notes' => 'Horario de emergencias',
                    ]);
                }
            }
        }

        $this->command->info('Disponibilidad de doctores creada exitosamente!');
    }
}
