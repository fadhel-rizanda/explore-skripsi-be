<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public static function baseRules(): array
    {
        return [
            'street' => 'sometimes|filled|string|max:500',
            'city' => 'sometimes|filled|string|max:100',
            'state' => 'sometimes|filled|string|max:100',
            'zip_code' => 'sometimes|filled|string|max:20',
            'country' => 'sometimes|filled|string|max:100',
            'notes' => 'sometimes|string|max:1000',
            'link' => 'sometimes|url|max:255',
        ];
    }

    public static function prefixedRules(string $prefix = 'address'): array
    {
        return collect(self::baseRules())
            ->mapWithKeys(fn ($rule, $key) => [($prefix ? "{$prefix}." : '') . $key => $rule])
            ->toArray();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::baseRules();
    }
}
