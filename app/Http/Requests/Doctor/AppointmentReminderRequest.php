<?php

namespace App\Http\Requests\Doctor;

use App\Http\Requests\BaseRequest;

class AppointmentReminderRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'appointment_id' => 'required|exists:appointments,id',
            'when' => 'required|in:24h,2h',
            'title' => 'sometimes|string|max:150',
            'message' => 'sometimes|string|max:2000',
            'priority' => 'sometimes|integer|min:1|max:5',
        ];
    }
}


