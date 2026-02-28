<?php

namespace App\Http\Requests;

use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $provinceTable = (new Province())->getTable();
        $regencyTable = (new Regency())->getTable();
        $districtTable = (new District())->getTable();

        return [
            'street' => ['sometimes', 'nullable', 'string', 'max:500'],

            'province_id' => [
                'sometimes',
                'nullable',
                'string',
                Rule::exists($provinceTable, 'id'),
            ],

            'regency_id' => [
                'sometimes',
                'nullable',
                'string',
                Rule::exists($regencyTable, 'id')
                    ->where(
                        fn ($query) => $query->where('province_id', request()->input('province_id') ?? request()->input('address.province_id'))
                    ),
            ],

            'district_id' => [
                'sometimes',
                'nullable',
                'string',
                Rule::exists($districtTable, 'id')
                    ->where(
                        fn ($query) => $query->where('regency_id', request()->input('regency_id') ?? request()->input('address.regency_id'))
                    ),
            ],

            'zip_code' => ['sometimes', 'nullable', 'string', 'max:20'],

            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'link' => ['sometimes', 'nullable', 'url', 'max:255'],
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
