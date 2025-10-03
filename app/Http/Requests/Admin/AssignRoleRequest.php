<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignRoleRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado para hacer esta petición
     *
     * Verifica que el usuario autenticado tenga el permiso 'manage-users'
     * necesario para gestionar roles de otros usuarios.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('manage-users');
    }

    /**
     * Obtener las reglas de validación que se aplicarán a la petición
     *
     * Valida que el campo 'role' sea obligatorio y contenga uno de los
     * tres roles válidos del sistema: admin, doctor o patient.
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in(['admin', 'doctor', 'patient'])],
        ];
    }

    /**
     * Obtener los mensajes de error personalizados para las reglas de validación
     *
     * Proporciona mensajes más descriptivos cuando la validación falla,
     * ayudando al usuario a entender qué datos son requeridos.
     */
    public function messages(): array
    {
        return [
            'role.required' => 'The role field is required',
            'role.in' => 'The role must be one of: admin, doctor, patient'
        ];
    }
}
