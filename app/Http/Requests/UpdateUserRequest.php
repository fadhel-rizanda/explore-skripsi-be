<?php

namespace App\Http\Requests;

use App\Enums\ModelReferenceEnum;
use App\Models\User;
use App\Rules\OwnsAttachment;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
                'name' => 'sometimes|nullable|string|max:255',
                'phone' => 'sometimes|nullable|string|max:20|unique:' . (new User())->getTable() . ',phone,' . $this->user()->id . ',id',
                'about_me' => 'sometimes|nullable|string|max:1000',
                'open_to_special_needs' => 'sometimes|boolean',
                'attachment_id' => [
                    'sometimes',
                    'uuid',
                    new OwnsAttachment(
                        referenceType: ModelReferenceEnum::USER->value,
                        referenceId: $this->route('user') ? $this->route('user')->id : null,
                    ),
                ],
            ],
            UpdateAddressRequest::prefixedRules(''),
            UserBackgroundRequest::prefixedRules('')
        );
    }
}
