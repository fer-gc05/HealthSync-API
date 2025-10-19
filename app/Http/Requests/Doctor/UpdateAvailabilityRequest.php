<?php

namespace App\Http\Requests\Doctor;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class UpdateAvailabilityRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'day_of_week' => ['required', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'is_available' => ['boolean'],
            'max_appointments' => ['nullable', 'integer', 'min:1', 'max:50'],
            'specific_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
