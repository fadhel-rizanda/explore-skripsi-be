<?php

namespace App\Http\Requests;

use App\Rules\OwnsAttachment;
use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
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
            'content' => 'required_without:attachment_id|nullable|string|max:10000',
            'attachment_id' => [
                'required_without:content',
                'nullable',
                'uuid',
                new OwnsAttachment(),
            ],
        ];
    }
}
