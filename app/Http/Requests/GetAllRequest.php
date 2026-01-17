<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetAllRequest extends FormRequest
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
            'search' => 'sometimes|string|max:255',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'type' => 'sometimes|string|max:50',
            'status' => 'sometimes|string|max:50',
            'type_of_animal_id' => 'sometimes|uuid|exists:mt_all_tag,id',
            'age' => 'sometimes|string|in:Baby,Young,Adult,Senior',
            'tag_personality_id' => 'sometimes|uuid|exists:mt_all_tag,id',
            // 'sort_by' => 'sometimes|string|max:100',
            // 'order_by' => 'sometimes|string|in:asc,desc',
        ];
    }
}
