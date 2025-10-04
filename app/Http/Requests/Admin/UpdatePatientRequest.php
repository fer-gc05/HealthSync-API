<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class UpdatePatientRequest extends BaseRequest
{
    public function rules(): array
    {
        $userId = $this->route('user');
        
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $userId,
            'password' => 'sometimes|string|min:8|confirmed',
            'birth_date' => 'sometimes|date|before:today',
            'gender' => 'sometimes|in:male,female,other',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'blood_type' => 'nullable|string|max:10',
            'allergies' => 'nullable|string|max:1000',
            'current_medications' => 'nullable|string|max:1000',
            'insurance_number' => 'nullable|string|max:50',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
        ];
    }
}
