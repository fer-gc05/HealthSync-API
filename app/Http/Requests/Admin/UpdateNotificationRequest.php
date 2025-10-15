<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class UpdateNotificationRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:150',
            'message' => 'sometimes|required|string|max:2000',
            'type' => 'sometimes|required|in:appointment_reminder,appointment_cancelled,teleconsultation_ready,medical_record_updated,appointment_confirmed,appointment_rescheduled,new_message,system_alert',
            'user_id' => 'sometimes|nullable|exists:users,id',
            'recipient_id' => 'sometimes|nullable|exists:users,id',
            'appointment_id' => 'sometimes|nullable|exists:appointments,id',
            'medical_record_id' => 'sometimes|nullable|exists:medical_records,id',
            'priority' => 'sometimes|nullable|integer|min:1|max:5',
            'metadata' => 'sometimes|nullable|array',
        ];
    }
}


