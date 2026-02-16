<?php

namespace App\Http\Requests;

use App\Enums\ModelReferenceEnum;
use App\Models\AllTag;
use App\Rules\OwnsAttachment;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
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
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string|max:5000',
            'attachment_id' => [
                'sometimes',
                'uuid',
                new OwnsAttachment(
                    referenceType: ModelReferenceEnum::POST->value,
                    referenceId: $this->route('post') ? $this->route('post')->id : null,
                ),
            ],
            'tag_ids' => 'sometimes|array|min:1',
            'tag_ids.*' => 'uuid|exists:' . (new AllTag())->getTable() . ',id',
        ];
    }
}
