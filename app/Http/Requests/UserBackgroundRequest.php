<?php

namespace App\Http\Requests;

use App\Models\AllTag;
use Illuminate\Foundation\Http\FormRequest;

class UserBackgroundRequest extends FormRequest
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
            'personality' => 'sometimes|string|max:1000',
            'pet_experience' => 'sometimes|string|max:1000',
            'pet_preferences' => 'sometimes|string|max:1000',

            'personality_tags' => 'sometimes|array',
            'personality_tags.*' => 'uuid|exists:'. (new AllTag())->getTable() .',id',

            'pet_experience_tags' => 'sometimes|array',
            'pet_experience_tags.*' => 'uuid|exists:'. (new AllTag())->getTable() .',id',

            'pet_preferences_tags' => 'sometimes|array',
            'pet_preferences_tags.*' => 'uuid|exists:'. (new AllTag())->getTable() .',id',
        ];
    }
}
