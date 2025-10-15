<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class StoreNotificationRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:2000',
            'type' => 'required|in:appointment_reminder,appointment_cancelled,teleconsultation_ready,medical_record_updated,appointment_confirmed,appointment_rescheduled,new_message,system_alert',
            'user_id' => 'nullable|exists:users,id',
            'recipient_id' => 'required_without:user_id|exists:users,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'medical_record_id' => 'nullable|exists:medical_records,id',
            'priority' => 'nullable|integer|min:1|max:5',
            'metadata' => 'nullable|array',
        ];
    }
}


