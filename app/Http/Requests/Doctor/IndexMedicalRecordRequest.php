<?php

namespace App\Http\Requests\Doctor;

use App\Http\Requests\BaseRequest;

class IndexMedicalRecordRequest extends BaseRequest
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
            'patient_id' => 'nullable|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'has_prescriptions' => 'nullable|boolean',
            'has_files' => 'nullable|boolean',
            'q' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:50',
        ];
    }
}
