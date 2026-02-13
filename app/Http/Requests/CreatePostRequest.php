<?php

namespace App\Http\Requests;

use App\Models\AllTag;
use App\Models\Community;
use App\Rules\OwnsAttachment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePostRequest extends FormRequest
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
            'content' => 'required|string|max:5000',
            'community_id' => [
                'sometimes',
                'uuid',
                Rule::exists((new Community())->getTable(), 'id')
                    ->where('is_active', true),
            ],
            'attachment_id' => [
                'sometimes',
                'uuid',
                new OwnsAttachment(),
            ],
            'tag_ids' => 'sometimes|array|min:1',
            'tag_ids.*' => 'uuid|exists:' . (new AllTag())->getTable() . ',id',
        ];
    }
}
