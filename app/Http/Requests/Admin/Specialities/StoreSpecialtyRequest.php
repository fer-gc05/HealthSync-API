<?php

namespace App\Http\Requests\Admin\Specialities;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSpecialtyRequest extends FormRequest
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

    /**
     * Obtener los mensajes de error personalizados para las reglas de validación
     *
     * Proporciona mensajes más descriptivos cuando la validación falla,
     * ayudando al usuario a entender qué datos son requeridos.
     */

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('specialties', 'name')
                    ->whereNull('deleted_at')
            ],
            'description' => 'nullable|string|max:255',
            'active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The specialty name is required.',
            'name.unique' => 'A specialty with this name already exists.',
            'name.max' => 'The name cannot exceed 100 characters.',
            'description.max' => 'The description cannot exceed 255 characters.',
            'active.boolean' => 'The active field must be true or false.',
        ];
    }
}
