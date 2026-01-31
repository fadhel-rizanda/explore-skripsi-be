<?php

namespace App\Http\Requests;

use App\Enums\ModelReferenceEnum;
use App\Models\AllTag;
use App\Models\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateReportRequest extends FormRequest
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
            'reference_type' => ['required', Rule::in(ModelReferenceEnum::allValues())],
            'reference_id' => 'required|uuid',
            'notes' => 'required|string|max:2000',
            'status_id' => 'sometimes|uuid|exists:' . (new Status())->getTable() . ',id',
            'tag_ids' => 'sometimes|array',
            'tag_ids.*' => 'uuid|exists:' . (new AllTag())->getTable() . ',id',
        ];
    }
}
