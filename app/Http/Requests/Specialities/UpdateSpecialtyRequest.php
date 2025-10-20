<?php

namespace App\Http\Requests\Specialities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSpecialtyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('manage-specialties');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    public function rules(): array
    {
        $specialtyId = $this->route('specialty');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('specialties', 'name')
                    ->ignore($specialtyId)
                    ->whereNull('deleted_at')
            ],
            'description' => 'sometimes|nullable|string|max:255',
            'active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A specialty with this name already exists.',
            'name.max' => 'The name cannot exceed 100 characters.',
            'description.max' => 'The description cannot exceed 255 characters.',
            'active.boolean' => 'The active field must be true or false.',
        ];
    }
}
