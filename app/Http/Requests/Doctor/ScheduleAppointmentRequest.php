<?php

namespace App\Http\Requests\Doctor;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class ScheduleAppointmentRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'specialty_id' => ['required', 'exists:specialties,id'],
            'start_date' => ['required', 'date', 'after:now'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'type' => ['required', Rule::in(['presencial', 'virtual'])],
            'reason' => ['required', 'string', 'max:1000'],
            'urgent' => ['boolean'],
            'priority' => ['integer', 'min:1', 'max:5'],
        ];
    }
}
