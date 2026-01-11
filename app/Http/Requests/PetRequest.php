<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PetRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|uuid|exists:mt_user,id',
            'type_of_animal_id' => 'required|uuid|exists:mt_all_tag,id',
            'size' => 'required|string|in:Small,Medium,Large,Extra Large',
            'name' => 'required|string|max:50',
            'date_of_birth' => 'required|date',
            'gender' => 'required|string|in:Male,Female',
            'about' => 'required|string',
            'breed' => 'required|string|max:255',
            'special_needs' => 'required|boolean',
            // Profile pictures
            'profile_picture_ids' => 'required|array',
            'profile_picture_ids.*' => 'uuid|exists:mt_attachment,id',
            // Arrays for tags - REQUIRED
            'physique_ids' => 'required|array',
            'physique_ids.*' => 'uuid|exists:mt_all_tag,id',
            'personality_ids' => 'required|array',
            'personality_ids.*' => 'uuid|exists:mt_all_tag,id',
        ];  

    }
}


