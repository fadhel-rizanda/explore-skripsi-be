<?php

namespace App\Http\Requests;

use App\Models\AllTag;
use App\Models\Role;
use App\Models\Status;
use Illuminate\Foundation\Http\FormRequest;

class GetAllRequest extends FormRequest
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
        $tagTables = AllTag::TABLE;
        $statusTable = Status::TABLE;
        $roleTable = Role::TABLE;

        return [
            'search' => 'nullable|string|max:255',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'type' => 'sometimes|string|max:50',
            'status' => 'sometimes|string|max:50|exists:' . $statusTable . ',name',
            'type_of_animal_id' => 'sometimes|uuid|exists:' . $tagTables . ',id',
            'age' => 'sometimes|string|in:baby,young,adult,senior',
            'tag_personality_id' => 'sometimes|uuid|exists:' . $tagTables . ',id',
            'status_id' => 'sometimes|uuid|exists:' . $statusTable . ',id',
            'tag_id' => 'sometimes|uuid|exists:' . $tagTables . ',id',
            'role_id' => 'sometimes|uuid|exists:' . $roleTable . ',id',
            'sort_by' => 'sometimes|string|max:100',
            'order_by' => 'sometimes|string|in:asc,desc',
        ];
    }
}
