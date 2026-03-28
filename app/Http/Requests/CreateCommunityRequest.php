<?php

namespace App\Http\Requests;

use App\Models\AllTag;
use App\Models\Community;
use App\Models\User;
use App\Rules\OwnsAttachment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCommunityRequest extends FormRequest
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
                'name' => 'required|string|max:255|unique:' . Community::TABLE . ',name',
                'description' => 'sometimes|string|max:1000',
                'website' => 'sometimes|url|max:255',
                'attachment_id' => [
                    'sometimes',
                    'uuid',
                    new OwnsAttachment(),
                ],
                'tag_ids' => 'required|array|min:1',
                'tag_ids.*' => 'uuid|exists:' . AllTag::TABLE . ',id',
                'admin_ids' => 'sometimes|array',
                'admin_ids.*' => [
                    'uuid',
                    Rule::exists(User::TABLE, 'id')->where('is_active', true),
                ],
                'use_owner_address' => 'required|boolean',
            ],
            $this->use_owner_address
                ? []
                : CreateAddressRequest::prefixedRules(),
        );
    }
}
