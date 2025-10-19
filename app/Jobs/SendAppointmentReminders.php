<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Mail\AppointmentReminderMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendAppointmentReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $reminderType;

    /**
     * Create a new job instance.
     */
    public function __construct(string $reminderType = '24h')
    {
        $this->reminderType = $reminderType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $appointments = $this->getAppointmentsForReminder();

        foreach ($appointments as $appointment) {
            try {
                $this->sendReminderToPatient($appointment);
                $this->sendReminderToDoctor($appointment);

                Log::info('Appointment reminder sent', [
                    'appointment_id' => $appointment->id,
                    'reminder_type' => $this->reminderType,
                    'patient_email' => $appointment->patient->user->email,
                    'doctor_email' => $appointment->medicalStaff->user->email
                ]);
            } catch (\Exception $e) {
                Log::error('Error sending appointment reminder', [
                    'appointment_id' => $appointment->id,
                    'reminder_type' => $this->reminderType,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Obtener citas que necesitan recordatorio
     */
    private function getAppointmentsForReminder()
    {
        $query = Appointment::with(['patient.user', 'medicalStaff.user', 'specialty'])
            ->where('status', 'confirmada');

        switch ($this->reminderType) {
            case '24h':
                $query->whereBetween('start_date', [
                    now()->addDay()->startOfDay(),
                    now()->addDay()->endOfDay()
                ]);
                break;
            case '2h':
                $query->whereBetween('start_date', [
                    now()->addHours(2)->startOfHour(),
                    now()->addHours(2)->endOfHour()
                ]);
                break;
            case '30min':
                $query->whereBetween('start_date', [
                    now()->addMinutes(30)->startOfMinute(),
                    now()->addMinutes(30)->endOfMinute()
                ]);
                break;
        }

        return $query->get();
    }

    /**
     * Enviar recordatorio al paciente
     */
    private function sendReminderToPatient(Appointment $appointment): void
    {
        $patient = $appointment->patient->user;

        $reminderData = [
            'appointment' => $appointment,
            'patient' => $patient,
            'doctor' => $appointment->medicalStaff->user,
            'specialty' => $appointment->specialty,
            'reminder_type' => $this->reminderType,
            'meeting_link' => $appointment->isVirtual() ? $appointment->getTeleconsultationLink() : null,
            'meeting_password' => $appointment->isVirtual() ? $appointment->meeting_password : null,
        ];

        Mail::to($patient->email)->send(new AppointmentReminderMail($reminderData, 'patient'));
    }

    /**
     * Enviar recordatorio al doctor
     */
    private function sendReminderToDoctor(Appointment $appointment): void
    {
        $doctor = $appointment->medicalStaff->user;

        $reminderData = [
            'appointment' => $appointment,
            'patient' => $appointment->patient->user,
            'doctor' => $doctor,
            'specialty' => $appointment->specialty,
            'reminder_type' => $this->reminderType,
            'meeting_link' => $appointment->isVirtual() ? $appointment->getTeleconsultationLink() : null,
            'meeting_password' => $appointment->isVirtual() ? $appointment->meeting_password : null,
        ];

        Mail::to($doctor->email)->send(new AppointmentReminderMail($reminderData, 'doctor'));
    }
}
