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
            'size_id' => 'required|uuid|exists:mt_all_tag,id',
            'name' => 'required|string|max:50',
            'date_of_birth' => 'required|date',
            'gender_id' => 'required|uuid|exists:mt_all_tag,id',
            'about' => 'required|string',
            'breed' => 'required|string|max:255',
            'profile_picture' => 'required|string|url|max:255',
            'special_needs' => 'required|boolean',
            // Arrays untuk tags - WAJIB
            'physique_ids' => 'required|array',
            'physique_ids.*' => 'uuid|exists:mt_all_tag,id',
            'personality_ids' => 'required|array',
            'personality_ids.*' => 'uuid|exists:mt_all_tag,id',
        ];  

    }
}


