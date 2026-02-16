<?php

namespace App\Http\Requests;

use App\Models\AllTag;
use App\Rules\OwnsAttachment;
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
        // Check if this is an update request (route has 'id' parameter)
        $petId = $this->route('id');
        $isUpdate = !is_null($petId);

        return [
            'type_of_animal_id' => 'required|uuid|exists:' . (new AllTag())->getTable() . ',id',
            'size' => 'required|string|in:small,medium,large,extra large',
            'name' => 'required|string|max:50',
            'date_of_birth' => 'required|date',
            'gender' => 'required|string|in:male,female',
            'about' => 'required|string',
            'breed' => 'required|string|max:255',
            'special_needs' => 'required|boolean',
            // Profile pictures
            'profile_picture_ids' => [
                'required', 
                'array', 
                'min:1', 
                $isUpdate 
                    ? new OwnsAttachment('Pet', $petId)
                    : new OwnsAttachment()
            ],
            'profile_picture_ids.*' => 'uuid',
            // Arrays for tags - REQUIRED
            'physique_ids' => 'required|array|min:1',
            'physique_ids.*' => 'uuid|exists:' . (new AllTag())->getTable() . ',id',
            'personality_ids' => 'required|array|min:1',
            'personality_ids.*' => 'uuid|exists:' . (new AllTag())->getTable() . ',id',
            'additional_record_ids' => [
                'nullable', 
                'array', 
                $isUpdate 
                    ? new OwnsAttachment('Pet', $petId)
                    : new OwnsAttachment()
            ],
            'additional_record_ids.*' => 'uuid',
        ];

    }
}
