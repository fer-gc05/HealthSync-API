<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\SendToPatientRequest;
use App\Http\Requests\Doctor\AppointmentReminderRequest;
use App\Models\Appointment;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:150',
            'type' => 'nullable|string|in:appointment_reminder,appointment_cancelled,teleconsultation_ready,medical_record_updated,appointment_confirmed,appointment_rescheduled,new_message,system_alert',
            'read' => 'nullable|boolean',
            'appointment_id' => 'nullable|exists:appointments,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:50',
            'page' => 'nullable|integer|min:1',
            'sort_by' => 'nullable|string|in:id,created_at,updated_at,read_at,priority,type',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ]);

        $query = Notification::with(['sender', 'recipient', 'appointment', 'medicalRecord'])
            ->byRecipient(auth()->id())
            ->search($validated['q'] ?? null);

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if ($request->has('read')) {
            $read = filter_var($validated['read'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($read === true) {
                $query->whereNotNull('read_at');
            } elseif ($read === false) {
                $query->whereNull('read_at');
            }
        }

        if (!empty($validated['appointment_id'])) {
            $query->where('appointment_id', $validated['appointment_id']);
        }

        $query->dateRange($validated['date_from'] ?? null, $validated['date_to'] ?? null);

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = strtolower($validated['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = min(max((int)($validated['per_page'] ?? 15), 1), 50);

        return response()->json(['success' => true, 'data' => $query->paginate($perPage)]);
    }

    public function sendToPatient(SendToPatientRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $recipient = User::findOrFail($validated['patient_id']);
        if (!$recipient->hasRole('patient')) {
            abort(422, 'El destinatario debe tener rol de paciente');
        }

        $notification = Notification::create([
            'sender_id' => auth()->id(),
            'recipient_id' => $recipient->id,
            'user_id' => $recipient->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'metadata' => $validated['metadata'] ?? null,
            'appointment_id' => $validated['appointment_id'] ?? null,
            'medical_record_id' => $validated['medical_record_id'] ?? null,
            'priority' => $validated['priority'] ?? null,
            'read' => false,
        ]);

        return response()->json(['success' => true, 'data' => $notification], 201);
    }

    public function appointmentReminder(AppointmentReminderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $appointment = Appointment::findOrFail($validated['appointment_id']);

        // Asegurar que el doctor autenticado es el dueño de la cita
        if (isset($appointment->doctor_id) && $appointment->doctor_id !== auth()->id()) {
            abort(403);
        }

        $recipientId = $appointment->patient_id;

        $notification = Notification::create([
            'sender_id' => auth()->id(),
            'recipient_id' => $recipientId,
            'user_id' => $recipientId,
            'type' => 'appointment_reminder',
            'title' => $validated['title'] ?? 'Recordatorio de cita',
            'message' => $validated['message'] ?? 'Tienes una cita próxima.',
            'metadata' => [
                'when' => $validated['when'],
                'channel' => $appointment->mode ?? 'unknown',
            ],
            'appointment_id' => $appointment->id,
            'priority' => $validated['priority'] ?? 3,
            'read' => false,
        ]);

        return response()->json(['success' => true, 'data' => $notification], 201);
    }

    public function markRead(Notification $notification): JsonResponse
    {
        if ($notification->recipient_id !== auth()->id()) {
            abort(403);
        }
        $notification->markAsRead();
        return response()->json(['success' => true]);
    }

    public function unreadCount(): JsonResponse
    {
        $count = Notification::byRecipient(auth()->id())->whereNull('read_at')->count();
        return response()->json(['success' => true, 'data' => ['count' => $count]]);
    }
}


