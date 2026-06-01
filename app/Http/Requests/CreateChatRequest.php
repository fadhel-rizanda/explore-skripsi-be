<?php

namespace App\Http\Requests;

use App\Enums\ChatTypeEnum;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateChatRequest extends FormRequest
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
        $type = $this->input('type');
        $currentUserId = auth('api')->id();
        $userTable = User::TABLE;
        $isManual = $this->boolean('is_create_manually');

        return [
            'name' => [
                Rule::when(
                    $isManual,
                    ['required'],
                    ['nullable']
                ),
                'string',
                'max:255',
            ],
            'description' => 'nullable|string|max:1000',
            'type' => ['required', Rule::in(ChatTypeEnum::allValues())],
            'user_ids' => [
                'required',
                'array',
                Rule::when(
                    $type === ChatTypeEnum::PRIVATE->value,
                    ['size:1'],
                    ['min:2']
                ),
            ],
            'user_ids.*' => [
                'uuid',
                'distinct',
                Rule::notIn([$currentUserId]),
                Rule::exists($userTable, 'id')->where('is_active', true),
            ],

            'is_create_manually' => 'required|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_ids.required' => 'The user IDs are required.',
            'user_ids.array' => 'The user IDs must be an array.',
            'user_ids.size' => 'You must select exactly one user for a private chat.',
            'user_ids.min' => 'You must select at least two users for a group chat.',
            'user_ids.*.exists' => 'The selected user is invalid or deactivated.',
            'user_ids.*.not_in' => 'You cannot initiate a chat with yourself.',
            'user_ids.*.distinct' => 'Duplicate user IDs are not allowed.',
            'user_ids.*.uuid' => 'Each selected user ID must be a valid UUID.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_ids.*' => 'user',
        ];
    }
}
