<?php

namespace App\Http\Requests;

use App\Models\MeetNGreet;
use Illuminate\Foundation\Http\FormRequest;

class CreateScheduleRequest extends FormRequest
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
                'scheduled_time' => 'required|date|after_or_equal:today',
                'meet_n_greet_id' => 'nullable|uuid|exists:' . MeetNGreet::TABLE . ',id',
            ],
            CreateAddressRequest::prefixedRules('address')
        );
    }
}
