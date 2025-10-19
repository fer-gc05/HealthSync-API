<?php

namespace App\Services;

use App\Models\Appointment;
use App\Jobs\SyncAppointmentWithGoogleCalendar;
use Illuminate\Support\Facades\Log;

class AppointmentCalendarService
{
    protected $calendarService;

    public function __construct(GoogleCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    /**
     * Sincronizar cita con Google Calendar
     */
    public function syncAppointment(Appointment $appointment, string $action = 'create'): void
    {
        // Sincronizar TODAS las citas (presenciales y virtuales) - DIRECTAMENTE
        try {
            if ($action === 'create') {
                $this->createCalendarEvent($appointment);
            } elseif ($action === 'update') {
                $this->updateCalendarEvent($appointment);
            } elseif ($action === 'delete') {
                $this->deleteCalendarEvent($appointment);
            }
        } catch (\Exception $e) {
            Log::error('Error syncing appointment with Google Calendar', [
                'appointment_id' => $appointment->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Crear evento en Google Calendar
     */
    public function createCalendarEvent(Appointment $appointment): void
    {
        // Crear evento para TODAS las citas (presenciales y virtuales)

        $eventData = [
            'summary' => "Cita médica - {$appointment->patient->user->name}",
            'description' => $this->buildEventDescription($appointment),
            'start' => $appointment->start_date->format('c'),
            'end' => $appointment->end_date->format('c'),
            'attendees' => [
                $appointment->patient->user->email,
                $appointment->medicalStaff->user->email,
            ],
        ];

        $event = $this->calendarService->createEvent($eventData);

        $updateData = [
            'google_event_id' => $event->getId(),
            'calendar_synced' => true,
            'google_event_data' => json_encode($event->toSimpleObject()),
        ];

        // Solo generar Google Meet para citas virtuales
        if ($appointment->isVirtual()) {
            $meetingLink = $this->generateMeetingLink($appointment);
            $meetingPassword = $this->generateMeetingPassword();
            $updateData['meeting_link'] = $meetingLink;
            $updateData['meeting_password'] = $meetingPassword;

            Log::info('Generated meeting link for virtual appointment', [
                'appointment_id' => $appointment->id,
                'meeting_link' => $meetingLink,
                'meeting_password' => $meetingPassword
            ]);
        }

        $appointment->update($updateData);

        Log::info('Appointment synced with Google Calendar', [
            'appointment_id' => $appointment->id,
            'google_event_id' => $event->getId()
        ]);
    }

    /**
     * Actualizar evento en Google Calendar
     */
    public function updateCalendarEvent(Appointment $appointment): void
    {
        if (!$appointment->google_event_id) {
            return;
        }

        $eventData = [
            'summary' => "Cita médica - {$appointment->patient->user->name}",
            'description' => $this->buildEventDescription($appointment),
            'start' => [
                'dateTime' => $appointment->start_date->format('c'),
            ],
            'end' => [
                'dateTime' => $appointment->end_date->format('c'),
            ],
        ];

        $this->calendarService->updateEvent($appointment->google_event_id, $eventData);

        Log::info('Appointment updated in Google Calendar', [
            'appointment_id' => $appointment->id,
            'google_event_id' => $appointment->google_event_id
        ]);
    }

    /**
     * Eliminar evento de Google Calendar
     */
    public function deleteCalendarEvent(Appointment $appointment): void
    {
        if (!$appointment->google_event_id) {
            return;
        }

        $this->calendarService->deleteEvent($appointment->google_event_id);

        $appointment->update([
            'google_event_id' => null,
            'calendar_synced' => false,
        ]);

        Log::info('Appointment deleted from Google Calendar', [
            'appointment_id' => $appointment->id,
            'google_event_id' => $appointment->google_event_id
        ]);
    }

    /**
     * Construir descripción del evento
     */
    private function buildEventDescription(Appointment $appointment): string
    {
        $description = "Cita médica\n\n";
        $description .= "Paciente: {$appointment->patient->user->name}\n";
        $description .= "Doctor: {$appointment->medicalStaff->user->name}\n";
        $description .= "Especialidad: {$appointment->specialty->name}\n";
        $description .= "Motivo: {$appointment->reason}\n";
        $description .= "Tipo: " . ucfirst($appointment->type) . "\n";

        if ($appointment->urgent) {
            $description .= "⚠️ URGENTE\n";
        }

        return $description;
    }

    /**
     * Generar enlace de reunión
     */
    private function generateMeetingLink(Appointment $appointment): string
    {
        return "https://meet.google.com/" . \Str::random(10) . "-" . $appointment->id;
    }

    /**
     * Generar contraseña de reunión
     */
    private function generateMeetingPassword(): string
    {
        return \Str::random(8);
    }
}
