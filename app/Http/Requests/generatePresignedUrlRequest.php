<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class generatePresignedUrlRequest extends FormRequest
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
            'content_type' => 'required|string',
            'file_size' => 'required|integer|max:10485760', // Max 10MB
            'is_public' => 'nullable|boolean',
        ];
    }
}
