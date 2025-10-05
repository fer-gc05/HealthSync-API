<?php

namespace App\Http\Requests\Files;

use App\Http\Requests\BaseRequest;

class StoreMedicalRecordFileRequest extends BaseRequest
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
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png,gif,txt',
            'description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'A file is required.',
            'file.max' => 'The file size cannot exceed 10MB.',
            'file.mimes' => 'The file must be a PDF, DOC, DOCX, JPG, JPEG, PNG, GIF, or TXT file.',
        ];
    }
}
