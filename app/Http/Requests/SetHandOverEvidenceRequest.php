<?php

namespace App\Http\Requests;

use App\Rules\OwnsAttachment;
use Illuminate\Foundation\Http\FormRequest;

class SetHandOverEvidenceRequest extends FormRequest
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
            'attachment_ids' => ['required', 'array', 'min:1', new OwnsAttachment()],
            'attachment_ids.*' => 'uuid',
        ];
    }
}
