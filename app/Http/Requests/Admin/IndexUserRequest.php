<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseRequest;

class IndexUserRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => 'nullable|string|max:255',
            'role' => 'nullable|string|exists:roles,name',
            'with_trashed' => 'nullable|boolean',
            'only_trashed' => 'nullable|boolean',
            'sort_by' => 'nullable|string|in:name,email,created_at,updated_at',
            'sort_dir' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }

}
