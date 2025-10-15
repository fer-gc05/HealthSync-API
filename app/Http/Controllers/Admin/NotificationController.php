<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexNotificationRequest;
use App\Http\Requests\Admin\StoreNotificationRequest;
use App\Http\Requests\Admin\UpdateNotificationRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(IndexNotificationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Notification::with(['user', 'sender', 'recipient', 'appointment', 'medicalRecord'])
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

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
        ]);
    }

    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $recipientId = $validated['recipient_id'] ?? ($validated['user_id'] ?? null);
        $notification = new Notification();
        $notification->fill([
            'user_id' => $recipientId,
            'sender_id' => auth()->id(),
            'recipient_id' => $recipientId,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'metadata' => $validated['metadata'] ?? null,
            'appointment_id' => $validated['appointment_id'] ?? null,
            'medical_record_id' => $validated['medical_record_id'] ?? null,
            'priority' => $validated['priority'] ?? null,
            'read' => false,
            'read_at' => null,
        ]);
        $notification->save();

        return response()->json(['success' => true, 'data' => $notification->fresh()], 201);
    }

    public function show(Notification $notification): JsonResponse
    {
        $notification->load(['user', 'sender', 'recipient', 'appointment', 'medicalRecord']);
        return response()->json(['success' => true, 'data' => $notification]);
    }

    public function update(UpdateNotificationRequest $request, Notification $notification): JsonResponse
    {
        $notification->fill($request->validated());
        $notification->save();
        return response()->json(['success' => true, 'data' => $notification->fresh()]);
    }

    public function destroy(Notification $notification): JsonResponse
    {
        $notification->delete();
        return response()->json(['success' => true]);
    }

    public function markRead(Notification $notification): JsonResponse
    {
        $notification->markAsRead();
        return response()->json(['success' => true]);
    }

    public function markUnread(Notification $notification): JsonResponse
    {
        $notification->markAsUnread();
        return response()->json(['success' => true]);
    }

    public function sendToRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role' => 'required|string|in:admin,doctor,patient',
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:2000',
            'type' => 'required|in:appointment_reminder,appointment_cancelled,teleconsultation_ready,medical_record_updated,appointment_confirmed,appointment_rescheduled,new_message,system_alert',
            'metadata' => 'nullable|array',
            'appointment_id' => 'nullable|exists:appointments,id',
            'medical_record_id' => 'nullable|exists:medical_records,id',
            'priority' => 'nullable|integer|min:1|max:5',
        ]);

        $recipients = User::role($validated['role'])->pluck('id');

        $payloadBase = [
            'sender_id' => auth()->id(),
            'type' => $validated['type'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'metadata' => $validated['metadata'] ?? null,
            'appointment_id' => $validated['appointment_id'] ?? null,
            'medical_record_id' => $validated['medical_record_id'] ?? null,
            'priority' => $validated['priority'] ?? null,
            'read' => false,
            'read_at' => null,
        ];

        $insert = [];
        $now = now();
        foreach ($recipients as $recipientId) {
            $insert[] = array_merge($payloadBase, [
                'recipient_id' => $recipientId,
                'user_id' => $recipientId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (!empty($insert)) {
            Notification::insert($insert);
        }

        return response()->json(['success' => true, 'count' => count($insert)]);
    }

    public function sendToUsers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:2000',
            'type' => 'required|in:appointment_reminder,appointment_cancelled,teleconsultation_ready,medical_record_updated,appointment_confirmed,appointment_rescheduled,new_message,system_alert',
            'metadata' => 'nullable|array',
            'appointment_id' => 'nullable|exists:appointments,id',
            'medical_record_id' => 'nullable|exists:medical_records,id',
            'priority' => 'nullable|integer|min:1|max:5',
        ]);

        $payloadBase = [
            'sender_id' => auth()->id(),
            'type' => $validated['type'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'metadata' => $validated['metadata'] ?? null,
            'appointment_id' => $validated['appointment_id'] ?? null,
            'medical_record_id' => $validated['medical_record_id'] ?? null,
            'priority' => $validated['priority'] ?? null,
            'read' => false,
            'read_at' => null,
        ];

        $insert = [];
        $now = now();
        foreach ($validated['user_ids'] as $recipientId) {
            $insert[] = array_merge($payloadBase, [
                'recipient_id' => $recipientId,
                'user_id' => $recipientId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Notification::insert($insert);

        return response()->json(['success' => true, 'count' => count($insert)]);
    }

    public function stats(Request $request): JsonResponse
    {
        $total = Notification::count();
        $unread = Notification::whereNull('read_at')->count();
        $byType = Notification::selectRaw('type, COUNT(*) as count')->groupBy('type')->pluck('count', 'type');

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'unread' => $unread,
                'by_type' => $byType,
            ],
        ]);
    }
}


