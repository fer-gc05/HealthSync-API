<?php

namespace App\Http\Requests\Patient;

use App\Http\Requests\BaseRequest;

class RescheduleAppointmentRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'after:now'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

}
