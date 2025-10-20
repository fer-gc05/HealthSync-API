<?php

namespace App\Http\Requests\Specialities;

use Illuminate\Foundation\Http\FormRequest;

class IndexSpecialtyRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para esta petición
     */
    public function authorize(): bool
    {
        return true; // La autorización se maneja en el middleware
    }

    /**
     * Reglas de validación para listado de especialidades
     */
    public function rules(): array
    {
        $rules = [
            'q' => 'sometimes|string|max:255',
            'sort_by' => 'sometimes|string|in:name,created_at,updated_at',
            'sort_dir' => 'sometimes|string|in:asc,desc',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ];

        // Filtros adicionales solo para admin
        if (auth()->check() && auth()->user()->hasRole('admin')) {
            $rules['active'] = 'sometimes|boolean';
            $rules['with_trashed'] = 'sometimes|boolean';
            $rules['only_trashed'] = 'sometimes|boolean';
        }

        return $rules;
    }

    /**
     * Mensajes de error personalizados
     */
    public function messages(): array
    {
        return [
            'q.string' => 'The search term must be a valid string.',
            'q.max' => 'The search term cannot exceed 255 characters.',
            'sort_by.in' => 'Sort field must be one of: name, created_at, updated_at.',
            'sort_dir.in' => 'Sort direction must be either asc or desc.',
            'per_page.integer' => 'Per page value must be an integer.',
            'per_page.min' => 'Per page value must be at least 1.',
            'per_page.max' => 'Per page value cannot exceed 100.',
            'page.integer' => 'Page number must be an integer.',
            'page.min' => 'Page number must be at least 1.',
            'active.boolean' => 'Active filter must be a boolean value.',
            'with_trashed.boolean' => 'With trashed filter must be a boolean value.',
            'only_trashed.boolean' => 'Only trashed filter must be a boolean value.',
        ];
    }
}
