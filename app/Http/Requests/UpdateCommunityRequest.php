<?php

namespace App\Http\Requests;

use App\Enums\ModelReferenceEnum;
use App\Models\AllTag;
use App\Models\Community;
use App\Models\User;
use App\Rules\OwnsAttachment;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCommunityRequest extends FormRequest
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
        return array_merge(
            [
                'name' => 'sometimes|string|max:255|unique:' . (new Community())->getTable() . ',name' . ($this->route('community') ? ',' . $this->route('community')->id : ''),
                'description' => 'sometimes|string|max:1000',
                'website' => 'sometimes|url|max:255',
                'attachment_id' => [
                    'sometimes',
                    'uuid',
                    new OwnsAttachment(
                        referenceType: ModelReferenceEnum::COMMUNITY->value,
                        referenceId: $this->route('community') ? $this->route('community')->id : null,
                    ),
                ],
                'tag_ids' => 'sometimes|array|min:1',
                'tag_ids.*' => 'uuid|exists:' . (new AllTag())->getTable() . ',id',
            ],
            UpdateAddressRequest::prefixedRules(),
        );
    }
}
