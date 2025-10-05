<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class StoreMedicalRecordRequest extends BaseRequest
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
            'appointment_id' => 'required|exists:appointments,id',
            'patient_id' => 'required|exists:patients,id',
            'medical_staff_id' => 'required|exists:medical_staff,id',
            'subjective' => 'nullable|string|max:2000',
            'objective' => 'nullable|string|max:2000',
            'assessment' => 'nullable|string|max:2000',
            'plan' => 'nullable|string|max:2000',
            'vital_signs' => 'nullable|array',
            'vital_signs.blood_pressure' => 'nullable|string|regex:/^\d+\/\d+$/',
            'vital_signs.heart_rate' => 'nullable|integer|min:30|max:200',
            'vital_signs.temperature' => 'nullable|numeric|min:30|max:45',
            'vital_signs.respiratory_rate' => 'nullable|integer|min:8|max:40',
            'vital_signs.oxygen_saturation' => 'nullable|numeric|min:70|max:100',
            'vital_signs.weight' => 'nullable|numeric|min:1|max:500',
            'vital_signs.height' => 'nullable|numeric|min:30|max:250',
            'prescriptions' => 'nullable|string|max:2000',
            'recommendations' => 'nullable|string|max:2000',
        ];
    }
}
