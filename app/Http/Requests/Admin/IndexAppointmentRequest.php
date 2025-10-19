<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class IndexAppointmentRequest extends BaseRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'doctor_id' => ['nullable', 'exists:medical_staff,id'],
            'patient_id' => ['nullable', 'exists:patients,id'],
            'specialty_id' => ['nullable', 'exists:specialties,id'],
            'type' => ['nullable', Rule::in(['presencial', 'virtual'])],
            'status' => ['nullable', Rule::in(['programada', 'confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio'])],
            'urgent' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:5'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'sort_by' => ['nullable', Rule::in(['start_date', 'end_date', 'created_at', 'priority', 'status'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
