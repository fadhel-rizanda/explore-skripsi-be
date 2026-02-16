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

        return [
            'name' => [
                Rule::when(
                    $type === ChatTypeEnum::PUBLIC->value,
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
                Rule::notIn(array_filter([$currentUserId])),
                Rule::exists($userTable, 'id')->where('is_active', true),
            ],
        ];
    }
}
