<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\IndexMyNotificationRequest;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function index(IndexMyNotificationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Notification::with(['sender', 'appointment', 'medicalRecord'])
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

    public function show(Notification $notification): JsonResponse
    {
        abort_unless($notification->recipient_id === auth()->id(), 403);
        $notification->load(['sender', 'appointment', 'medicalRecord']);
        return response()->json(['success' => true, 'data' => $notification]);
    }

    public function markRead(Notification $notification): JsonResponse
    {
        abort_unless($notification->recipient_id === auth()->id(), 403);
        $notification->markAsRead();
        return response()->json(['success' => true]);
    }

    public function unreadCount(): JsonResponse
    {
        $count = Notification::byRecipient(auth()->id())->whereNull('read_at')->count();
        return response()->json(['success' => true, 'data' => ['count' => $count]]);
    }
}


