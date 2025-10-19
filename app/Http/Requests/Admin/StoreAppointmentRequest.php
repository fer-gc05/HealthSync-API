<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'medical_staff_id' => ['nullable', 'exists:medical_staff,id'],
            'specialty_id' => ['required', 'exists:specialties,id'],
            'start_date' => ['required', 'date', 'after:now'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'type' => ['required', Rule::in(['presencial', 'virtual'])],
            'status' => ['nullable', Rule::in(['programada', 'confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio'])],
            'reason' => ['required', 'string', 'max:1000'],
            'urgent' => ['boolean'],
            'priority' => ['integer', 'min:1', 'max:5'],
            'video_url' => ['nullable', 'url'],
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
            'attendance_status' => ['nullable', Rule::in(['asistio', 'no_asistio', 'cancelada'])],
            'attendance_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
