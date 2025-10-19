<?php

namespace App\Http\Requests\Doctor;

use App\Http\Requests\BaseRequest;

class SendToPatientRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'patient_id' => 'required|exists:users,id',
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:2000',
            'type' => 'required|in:appointment_reminder,appointment_cancelled,teleconsultation_ready,medical_record_updated,appointment_confirmed,appointment_rescheduled,new_message,system_alert',
            'appointment_id' => 'nullable|exists:appointments,id',
            'medical_record_id' => 'nullable|exists:medical_records,id',
            'priority' => 'nullable|integer|min:1|max:5',
            'metadata' => 'nullable|array',
        ];
    }
}


