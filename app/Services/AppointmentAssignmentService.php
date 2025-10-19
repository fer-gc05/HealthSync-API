<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\MedicalStaff;
use App\Models\DoctorAvailability;
use App\Models\AppointmentWaitlist;
use App\Models\Specialty;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AppointmentAssignmentService
{
    /**
     * Asignar el doctor óptimo para una cita
     */
    public function assignOptimalDoctor(int $specialtyId, $startDate, $endDate): ?MedicalStaff
    {
        $availableDoctors = $this->getAvailableDoctors($specialtyId, $startDate, $endDate);

        if ($availableDoctors->isEmpty()) {
            return null;
        }

        return $this->calculateOptimalAssignment($availableDoctors, $startDate, $endDate);
    }

    /**
     * Obtener doctores disponibles para una especialidad y horario
     */
    public function getAvailableDoctors(int $specialtyId, $startDate, $endDate): Collection
    {
        $startDateTime = Carbon::parse($startDate);
        $endDateTime = Carbon::parse($endDate);

        return MedicalStaff::where('specialty_id', $specialtyId)
            ->where('active', true)
            ->whereDoesntHave('appointments', function ($query) use ($startDateTime, $endDateTime) {
                $query->whereBetween('start_date', [$startDateTime, $endDateTime])
                    ->where('status', '!=', 'cancelada');
            })
            ->whereHas('availability', function ($query) use ($startDateTime) {
                $this->addAvailabilityConstraints($query, $startDateTime);
            })
            ->get();
    }

    /**
     * Calcular la asignación óptima
     */
    private function calculateOptimalAssignment(Collection $doctors, $startDate, $endDate): ?MedicalStaff
    {
        $scores = [];

        foreach ($doctors as $doctor) {
            $score = $this->calculateDoctorScore($doctor, $startDate, $endDate);
            $scores[$doctor->id] = $score;
        }

        arsort($scores);
        $bestDoctorId = array_key_first($scores);

        return MedicalStaff::find($bestDoctorId);
    }

    /**
     * Calcular puntuación del doctor
     */
    private function calculateDoctorScore(MedicalStaff $doctor, $startDate, $endDate): float
    {
        $score = 0;

        // Factor de carga de trabajo (menos citas = mayor puntuación)
        $appointmentCount = $doctor->appointments()
            ->whereBetween('start_date', [
                Carbon::parse($startDate)->startOfWeek(),
                Carbon::parse($startDate)->endOfWeek()
            ])
            ->where('status', '!=', 'cancelada')
            ->count();

        $score += max(0, 10 - $appointmentCount);

        // Factor de especialización
        $score += $doctor->experience_years * 0.5;

        // Factor de disponibilidad
        $availabilityScore = $this->getAvailabilityScore($doctor, $startDate);
        $score += $availabilityScore;

        // Factor de preferencia del paciente (si aplica)
        $preferenceScore = $this->getPreferenceScore($doctor);
        $score += $preferenceScore;

        return $score;
    }

    /**
     * Obtener puntuación de disponibilidad
     */
    private function getAvailabilityScore(MedicalStaff $doctor, $startDate): float
    {
        $startDateTime = Carbon::parse($startDate);
        $dayOfWeek = strtolower($startDateTime->format('l'));

        $availability = DoctorAvailability::where('medical_staff_id', $doctor->id)
            ->where('is_available', true)
            ->where(function ($query) use ($dayOfWeek, $startDateTime) {
                $query->where('day_of_week', $dayOfWeek)
                      ->orWhere('specific_date', $startDateTime->format('Y-m-d'));
            })
            ->first();

        if (!$availability) {
            return 0;
        }

        // Calcular duración de disponibilidad
        $duration = Carbon::parse($availability->start_time)
            ->diffInMinutes(Carbon::parse($availability->end_time));

        return min(5, $duration / 60); // Máximo 5 puntos por disponibilidad
    }

    /**
     * Obtener puntuación de preferencia
     */
    private function getPreferenceScore(MedicalStaff $doctor): float
    {
        // Aquí se pueden implementar factores como:
        // - Preferencias del paciente
        // - Historial de citas exitosas
        // - Calificaciones del doctor
        return 0;
    }

    /**
     * Agregar a lista de espera
     */
    public function addToWaitlist(array $appointmentData): AppointmentWaitlist
    {
        // Obtener la siguiente posición en la lista
        $nextPosition = AppointmentWaitlist::where('specialty_id', $appointmentData['specialty_id'])
            ->waiting()
            ->max('position') + 1;

        return AppointmentWaitlist::create([
            'patient_id' => $appointmentData['patient_id'],
            'specialty_id' => $appointmentData['specialty_id'],
            'preferred_doctor_id' => $appointmentData['medical_staff_id'] ?? null,
            'type' => $appointmentData['type'],
            'reason' => $appointmentData['reason'],
            'urgent' => $appointmentData['urgent'] ?? false,
            'priority' => $appointmentData['priority'] ?? 1,
            'position' => $nextPosition,
            'preferred_date' => $appointmentData['start_date'] ?? null,
        ]);
    }

    /**
     * Procesar lista de espera
     */
    public function processWaitlist(): array
    {
        $processed = [];

        $waitlistItems = AppointmentWaitlist::waiting()
            ->orderedByPriority()
            ->get();

        foreach ($waitlistItems as $item) {
            $assignedDoctor = $this->assignOptimalDoctor(
                $item->specialty_id,
                $item->preferred_date ?? now()->addDay(),
                $item->preferred_date ? Carbon::parse($item->preferred_date)->addHour() : now()->addDay()->addHour()
            );

            if ($assignedDoctor) {
                // Crear la cita
                $appointment = Appointment::create([
                    'patient_id' => $item->patient_id,
                    'medical_staff_id' => $assignedDoctor->id,
                    'specialty_id' => $item->specialty_id,
                    'start_date' => $item->preferred_date ?? now()->addDay(),
                    'end_date' => $item->preferred_date ? Carbon::parse($item->preferred_date)->addHour() : now()->addDay()->addHour(),
                    'type' => $item->type,
                    'status' => 'pendiente',
                    'reason' => $item->reason,
                    'urgent' => $item->urgent,
                    'priority' => $item->priority,
                    'auto_assigned' => true,
                ]);

                // Actualizar estado de la lista de espera
                $item->update([
                    'status' => 'assigned',
                    'position' => null
                ]);

                $processed[] = [
                    'waitlist_item' => $item,
                    'appointment' => $appointment,
                    'doctor' => $assignedDoctor
                ];
            }
        }

        return $processed;
    }

    /**
     * Obtener disponibilidad de doctores
     */
    public function getDoctorAvailability(string $date, ?int $specialtyId = null): Collection
    {
        $query = DoctorAvailability::with(['medicalStaff.user', 'medicalStaff.specialty'])
            ->where('is_available', true);

        if ($specialtyId) {
            $query->whereHas('medicalStaff', function ($q) use ($specialtyId) {
                $q->where('specialty_id', $specialtyId);
            });
        }

        $dateCarbon = Carbon::parse($date);
        $dayOfWeek = strtolower($dateCarbon->format('l'));

        return $query->where(function ($q) use ($dayOfWeek, $dateCarbon) {
            $q->where('day_of_week', $dayOfWeek)
              ->orWhere('specific_date', $dateCarbon->format('Y-m-d'));
        })->get();
    }

    /**
     * Obtener horarios disponibles
     */
    public function getAvailableSlots(int $specialtyId, string $date, string $type = 'presencial'): array
    {
        $slots = [];
        $dateCarbon = Carbon::parse($date);
        $dayOfWeek = strtolower($dateCarbon->format('l'));

        $doctors = MedicalStaff::where('specialty_id', $specialtyId)
            ->where('active', true)
            ->get();

        foreach ($doctors as $doctor) {
            $availability = DoctorAvailability::where('medical_staff_id', $doctor->id)
                ->where('is_available', true)
                ->where(function ($query) use ($dayOfWeek, $dateCarbon) {
                    $query->where('day_of_week', $dayOfWeek)
                          ->orWhere('specific_date', $dateCarbon->format('Y-m-d'));
                })
                ->first();

            if ($availability) {
                $startTime = Carbon::parse($date . ' ' . $availability->start_time->format('H:i:s'));
                $endTime = Carbon::parse($date . ' ' . $availability->end_time->format('H:i:s'));

                $currentTime = $startTime->copy();
                $slotDuration = 30; // 30 minutos por slot

                while ($currentTime->addMinutes($slotDuration)->lte($endTime)) {
                    // Verificar si el slot está disponible
                    if ($this->isSlotAvailable($doctor, $currentTime, $slotDuration)) {
                        $slots[] = [
                            'doctor' => $doctor->load('user'),
                            'start_time' => $currentTime->format('H:i'),
                            'end_time' => $currentTime->addMinutes($slotDuration)->format('H:i'),
                            'available' => true
                        ];
                    }
                }
            }
        }

        return $slots;
    }

    /**
     * Verificar si un slot está disponible
     */
    private function isSlotAvailable(MedicalStaff $doctor, Carbon $startTime, int $duration): bool
    {
        $endTime = $startTime->copy()->addMinutes($duration);

        $conflictingAppointments = Appointment::where('medical_staff_id', $doctor->id)
            ->where('status', '!=', 'cancelada')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_date', [$startTime, $endTime])
                      ->orWhereBetween('end_date', [$startTime, $endTime])
                      ->orWhere(function ($subQuery) use ($startTime, $endTime) {
                          $subQuery->where('start_date', '<=', $startTime)
                                  ->where('end_date', '>=', $endTime);
                      });
            })
            ->exists();

        return !$conflictingAppointments;
    }

    /**
     * Verificar si un doctor está disponible
     */
    public function isDoctorAvailable(MedicalStaff $doctor, string $date, string $type = 'presencial'): bool
    {
        $dateCarbon = Carbon::parse($date);

        $availability = DoctorAvailability::where('medical_staff_id', $doctor->id)
            ->where('is_available', true)
            ->where(function ($query) use ($dateCarbon) {
                $this->addAvailabilityConstraints($query, $dateCarbon);
            })
            ->exists();

        if (!$availability) {
            return false;
        }

        // Verificar conflictos de horarios
        $conflicts = Appointment::where('medical_staff_id', $doctor->id)
            ->where('status', '!=', 'cancelada')
            ->whereDate('start_date', $dateCarbon)
            ->exists();

        return !$conflicts;
    }

    /**
     * Agregar restricciones de disponibilidad a la consulta
     */
    private function addAvailabilityConstraints($query, Carbon $dateTime): void
    {
        $dayOfWeek = strtolower($dateTime->format('l'));

        $query->where('is_available', true)
            ->where(function ($subQuery) use ($dayOfWeek, $dateTime) {
                $subQuery->where('day_of_week', $dayOfWeek)
                        ->orWhere('specific_date', $dateTime->format('Y-m-d'));
            })
            ->where('start_time', '<=', $dateTime->format('H:i:s'))
            ->where('end_time', '>=', $dateTime->format('H:i:s'));
    }
}
