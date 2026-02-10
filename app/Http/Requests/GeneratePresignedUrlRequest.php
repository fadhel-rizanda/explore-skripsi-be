<?php

namespace App\Http\Requests;

use App\Enums\ModelReferenceEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GeneratePresignedUrlRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'filename' => 'required|string',
            'mime_type' => 'required|string',
            'file_size' => 'required|integer|max:10485760', // Max 10MB
            'is_public' => 'nullable|boolean',
            'reference_by' => ['nullable', Rule::in(ModelReferenceEnum::allValues())],
            'reference_id' => 'nullable|uuid',
        ];
    }
}
