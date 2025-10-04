<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class UpdateDoctorRequest extends BaseRequest
{
    public function rules(): array
    {
        $userId = $this->route('user');
        
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $userId,
            'password' => 'sometimes|string|min:8|confirmed',
            'professional_license' => 'sometimes|string|unique:medical_staff,professional_license,' . $userId . ',user_id',
            'specialty_id' => 'sometimes|exists:specialties,id',
            'subspecialty' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
            'appointment_duration' => 'nullable|integer|min:15|max:480',
            'work_schedule' => 'nullable|array',
        ];
    }
}
