<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class StoreDoctorRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'professional_license' => 'required|string|unique:medical_staff',
            'specialty_id' => 'required|exists:specialties,id',
            'subspecialty' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
            'appointment_duration' => 'nullable|integer|min:15|max:480',
            'work_schedule' => 'nullable|array',
        ];
    }
}
