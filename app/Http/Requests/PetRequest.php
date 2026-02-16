<?php

namespace App\Http\Requests;

use App\Enums\ModelReferenceEnum;
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
        $petId = $this->route('id') ? $this->route('id')->id : null;
        $allTagTable = AllTag::TABLE;

        return [
            'type_of_animal_id' => 'required|uuid|exists:' . $allTagTable . ',id',
            'size' => 'required|string|in:small,medium,large,extra large',
            'name' => 'required|string|max:50',
            'date_of_birth' => 'required|date',
            'gender' => 'required|string|in:male,female',
            'about' => 'required|string',
            'breed' => 'required|string|max:255',
            'special_needs' => 'required|boolean',
            // Profile pictures
            'profile_picture_ids' => ['required', 'array', 'min:1', new OwnsAttachment(
                referenceType: ModelReferenceEnum::PET->value,
                referenceId: $petId
            )],
            'profile_picture_ids.*' => 'uuid',
            // Arrays for tags - REQUIRED
            'physique_ids' => 'required|array|min:1',
            'physique_ids.*' => 'uuid|exists:' . $allTagTable . ',id',
            'personality_ids' => 'required|array|min:1',
            'personality_ids.*' => 'uuid|exists:' . $allTagTable . ',id',
            'additional_record_ids' => ['nullable', 'array', new OwnsAttachment(
                referenceType: ModelReferenceEnum::PET->value,
                referenceId: $petId
            )],
            'additional_record_ids.*' => 'uuid',
        ];

    }
}
