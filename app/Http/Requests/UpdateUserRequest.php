<?php

namespace App\Http\Requests;

use App\Models\Attachment;
use App\Models\User;
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
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:' . (new User())->getTable() . ',email,' . $this->user()->id . ',id',
                'phone' => 'sometimes|string|max:20|unique:' . (new User())->getTable() . ',phone,' . $this->user()->id . ',id',
                'about_me' => 'sometimes|string|max:1000',
                'open_to_special_needs' => 'sometimes|boolean',
                'attachment_id' => 'sometimes|uuid|exists:' . (new Attachment())->getTable() . ',id',
            ],
            (new UpdateAddressRequest())->rules(),
            (new UserBackgroundRequest())->rules()
        );
    }
}
