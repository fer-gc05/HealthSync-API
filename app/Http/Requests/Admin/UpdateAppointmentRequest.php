<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['sometimes', 'exists:patients,id'],
            'medical_staff_id' => ['nullable', 'exists:medical_staff,id'],
            'specialty_id' => ['sometimes', 'exists:specialties,id'],
            'start_date' => ['sometimes', 'date', 'after:now'],
            'end_date' => ['sometimes', 'date', 'after:start_date'],
            'type' => ['sometimes', Rule::in(['presencial', 'virtual'])],
            'status' => ['sometimes', Rule::in(['programada', 'confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio'])],
            'reason' => ['sometimes', 'string', 'max:1000'],
            'urgent' => ['boolean'],
            'priority' => ['integer', 'min:1', 'max:5'],
            'video_url' => ['nullable', 'url'],
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
            'attendance_status' => ['nullable', Rule::in(['asistio', 'no_asistio', 'cancelada'])],
            'attendance_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
