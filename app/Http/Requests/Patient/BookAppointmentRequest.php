<?php

namespace App\Http\Requests\Patient;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class BookAppointmentRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'medical_staff_id' => ['nullable', 'exists:medical_staff,id'],
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
