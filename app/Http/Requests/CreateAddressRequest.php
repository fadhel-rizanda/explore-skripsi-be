<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAddressRequest extends FormRequest
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
            'street' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'zip_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'link' => 'nullable|url|max:255',
        ];
    }

    public static function prefixedRules(string $prefix = 'address'): array
    {
        return collect(self::baseRules())
            ->mapWithKeys(fn ($rule, $key) => ["{$prefix}.{$key}" => $rule])
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
