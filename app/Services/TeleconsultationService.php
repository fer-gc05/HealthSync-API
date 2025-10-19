<?php

namespace App\Services;

use App\Models\Appointment;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Str;

class TeleconsultationService
{
    protected $calendarService;

    public function __construct(GoogleCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    /**
     * Crear teleconsulta
     */
    public function createTeleconsultation(Appointment $appointment): array
    {
        if (!$appointment->isVirtual()) {
            throw new \Exception('La cita no es virtual');
        }

        // Generar enlace de reunión
        $meetingLink = $this->generateMeetingLink($appointment);
        $meetingPassword = $this->generateMeetingPassword();

        // Crear evento en Google Calendar
        $googleEventId = $this->createGoogleCalendarEvent($appointment, $meetingLink);

        // Actualizar la cita
        $appointment->update([
            'google_event_id' => $googleEventId,
            'meeting_link' => $meetingLink,
            'meeting_password' => $meetingPassword,
            'calendar_synced' => true,
        ]);

        return [
            'appointment' => $appointment,
            'meeting_link' => $meetingLink,
            'meeting_password' => $meetingPassword,
            'google_event_id' => $googleEventId,
        ];
    }

    /**
     * Iniciar teleconsulta
     */
    public function startTeleconsultation(Appointment $appointment): array
    {
        if (!$appointment->isVirtual()) {
            throw new \Exception('La cita no es virtual');
        }

        if ($appointment->status !== 'confirmada') {
            throw new \Exception('La cita debe estar confirmada para iniciar la teleconsulta');
        }

        // Actualizar estado
        $appointment->update(['status' => 'en_progreso']);

        // Actualizar evento en Google Calendar
        $this->updateGoogleCalendarEvent($appointment, 'en_progreso');

        return [
            'appointment' => $appointment,
            'meeting_link' => $appointment->getTeleconsultationLink(),
            'meeting_password' => $appointment->meeting_password,
            'status' => 'iniciada',
        ];
    }

    /**
     * Finalizar teleconsulta
     */
    public function endTeleconsultation(Appointment $appointment, ?string $notes = null): array
    {
        if (!$appointment->isVirtual()) {
            throw new \Exception('La cita no es virtual');
        }

        if ($appointment->status !== 'en_progreso') {
            throw new \Exception('La teleconsulta no está en progreso');
        }

        // Actualizar estado
        $appointment->update([
            'status' => 'completada',
            'attendance_status' => 'asistio',
            'attendance_notes' => $notes,
        ]);

        // Actualizar evento en Google Calendar
        $this->updateGoogleCalendarEvent($appointment, 'completada');

        return [
            'appointment' => $appointment,
            'status' => 'finalizada',
            'notes' => $notes,
        ];
    }

    /**
     * Cancelar teleconsulta
     */
    public function cancelTeleconsultation(Appointment $appointment, string $reason): array
    {
        if (!$appointment->isVirtual()) {
            throw new \Exception('La cita no es virtual');
        }

        // Actualizar estado
        $appointment->update([
            'status' => 'cancelada',
            'cancellation_reason' => $reason,
        ]);

        // Eliminar evento de Google Calendar
        if ($appointment->google_event_id) {
            $this->deleteGoogleCalendarEvent($appointment);
        }

        return [
            'appointment' => $appointment,
            'status' => 'cancelada',
            'reason' => $reason,
        ];
    }

    /**
     * Obtener información de la teleconsulta
     */
    public function getTeleconsultationInfo(Appointment $appointment): array
    {
        if (!$appointment->isVirtual()) {
            throw new \Exception('La cita no es virtual');
        }

        return [
            'appointment' => $appointment->load(['patient.user', 'medicalStaff.user', 'specialty']),
            'meeting_link' => $appointment->getTeleconsultationLink(),
            'meeting_password' => $appointment->meeting_password,
            'google_event_id' => $appointment->google_event_id,
            'calendar_synced' => $appointment->calendar_synced,
            'status' => $appointment->status,
            'can_start' => $this->canStartTeleconsultation($appointment),
            'can_join' => $this->canJoinTeleconsultation($appointment),
        ];
    }

    /**
     * Verificar si se puede iniciar la teleconsulta
     */
    public function canStartTeleconsultation(Appointment $appointment): bool
    {
        return $appointment->isVirtual()
            && $appointment->status === 'confirmada'
            && $appointment->start_date <= now()->addMinutes(15); // 15 minutos antes
    }

    /**
     * Verificar si se puede unir a la teleconsulta
     */
    public function canJoinTeleconsultation(Appointment $appointment): bool
    {
        return $appointment->isVirtual()
            && in_array($appointment->status, ['confirmada', 'en_progreso'])
            && $appointment->start_date <= now()->addMinutes(30); // 30 minutos después del inicio
    }

    /**
     * Crear evento en Google Calendar
     */
    private function createGoogleCalendarEvent(Appointment $appointment, string $meetingLink): string
    {
        $eventData = [
            'summary' => "Teleconsulta - {$appointment->patient->user->name}",
            'description' => $this->buildEventDescription($appointment, $meetingLink),
            'start' => [
                'dateTime' => $appointment->start_date->format('c'),
                'timeZone' => config('app.timezone', 'UTC'),
            ],
            'end' => [
                'dateTime' => $appointment->end_date->format('c'),
                'timeZone' => config('app.timezone', 'UTC'),
            ],
            'attendees' => [
                [
                    'email' => $appointment->patient->user->email,
                    'displayName' => $appointment->patient->user->name,
                ],
                [
                    'email' => $appointment->medicalStaff->user->email,
                    'displayName' => $appointment->medicalStaff->user->name,
                ],
            ],
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => 'teleconsulta-' . $appointment->id,
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet'
                    ]
                ]
            ],
        ];

        $event = $this->calendarService->createEvent($eventData);

        // Guardar datos del evento
        $appointment->update([
            'google_event_data' => $event->toArray(),
        ]);

        return $event->getId();
    }

    /**
     * Actualizar evento en Google Calendar
     */
    private function updateGoogleCalendarEvent(Appointment $appointment, string $status): void
    {
        if (!$appointment->google_event_id) {
            return;
        }

        $eventData = [
            'summary' => "Teleconsulta - {$appointment->patient->user->name} ({$status})",
            'description' => $this->buildEventDescription($appointment, $appointment->meeting_link),
        ];

        $this->calendarService->updateEvent($appointment->google_event_id, $eventData);
    }

    /**
     * Eliminar evento de Google Calendar
     */
    private function deleteGoogleCalendarEvent(Appointment $appointment): void
    {
        if (!$appointment->google_event_id) {
            return;
        }

        $this->calendarService->deleteEvent($appointment->google_event_id);

        $appointment->update([
            'google_event_id' => null,
            'calendar_synced' => false,
        ]);
    }

    /**
     * Construir descripción del evento
     */
    private function buildEventDescription(Appointment $appointment, string $meetingLink): string
    {
        $description = "Teleconsulta médica\n\n";
        $description .= "Paciente: {$appointment->patient->user->name}\n";
        $description .= "Doctor: {$appointment->medicalStaff->user->name}\n";
        $description .= "Especialidad: {$appointment->specialty->name}\n";
        $description .= "Motivo: {$appointment->reason}\n\n";
        $description .= "Enlace de la reunión: {$meetingLink}\n";

        if ($appointment->meeting_password) {
            $description .= "Contraseña: {$appointment->meeting_password}\n";
        }

        return $description;
    }

    /**
     * Generar enlace de reunión
     */
    private function generateMeetingLink(Appointment $appointment): string
    {
        $meetingId = Str::random(10) . '-' . $appointment->id;
        return "https://meet.google.com/{$meetingId}";
    }

    /**
     * Generar contraseña de reunión
     */
    private function generateMeetingPassword(): string
    {
        return Str::random(8);
    }

    /**
     * Obtener estadísticas de teleconsultas
     */
    public function getTeleconsultationStats(): array
    {
        $totalVirtual = Appointment::virtual()->count();
        $completedVirtual = Appointment::virtual()->where('status', 'completada')->count();
        $inProgressVirtual = Appointment::virtual()->where('status', 'en_progreso')->count();
        $syncedWithGoogle = Appointment::virtual()->syncedWithGoogle()->count();

        return [
            'total_virtual_appointments' => $totalVirtual,
            'completed_virtual_appointments' => $completedVirtual,
            'in_progress_virtual_appointments' => $inProgressVirtual,
            'synced_with_google' => $syncedWithGoogle,
            'completion_rate' => $totalVirtual > 0 ? round(($completedVirtual / $totalVirtual) * 100, 2) : 0,
            'sync_rate' => $totalVirtual > 0 ? round(($syncedWithGoogle / $totalVirtual) * 100, 2) : 0,
        ];
    }
}
