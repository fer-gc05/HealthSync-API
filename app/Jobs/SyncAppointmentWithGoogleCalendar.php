<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\AppointmentCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAppointmentWithGoogleCalendar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $appointment;
    protected $action;

    /**
     * Create a new job instance.
     */
    public function __construct(Appointment $appointment, string $action = 'create')
    {
        $this->appointment = $appointment;
        $this->action = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(AppointmentCalendarService $calendarService): void
    {
        try {
            switch ($this->action) {
                case 'create':
                    $calendarService->createCalendarEvent($this->appointment);
                    break;
                case 'update':
                    $calendarService->updateCalendarEvent($this->appointment);
                    break;
                case 'delete':
                    $calendarService->deleteCalendarEvent($this->appointment);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Error syncing appointment with Google Calendar: ' . $e->getMessage(), [
                'appointment_id' => $this->appointment->id,
                'action' => $this->action,
                'error' => $e->getMessage()
            ]);

            // Re-lanzar la excepción para que el job falle y se pueda reintentar
            throw $e;
        }
    }

}
