<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class IndexNotificationRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
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
        ];
    }
}


