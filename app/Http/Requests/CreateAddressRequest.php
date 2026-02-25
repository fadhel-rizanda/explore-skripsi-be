<?php

namespace App\Http\Requests;

use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $provinceTable = (new Province())->getTable();
        $regencyTable = (new Regency())->getTable();
        $districtTable = (new District())->getTable();

        return [
            'street' => ['required', 'string', 'max:500'],

            'province_id' => [
                'required',
                'string',
                Rule::exists($provinceTable, 'id'),
            ],

            'regency_id' => [
                'required',
                'string',
                Rule::exists($regencyTable, 'id')
                    ->where(
                        fn ($query) => $query->where('province_id', $this->province_id)
                    ),
            ],

            'district_id' => [
                'required',
                'string',
                Rule::exists($districtTable, 'id')
                    ->where(
                        fn ($query) => $query->where('regency_id', $this->regency_id)
                    ),
            ],

            'zip_code' => ['nullable', 'string', 'max:20'],

            'notes' => ['nullable', 'string', 'max:1000'],
            'link' => ['nullable', 'url', 'max:255'],
        ];
    }

    public static function prefixedRules(string $prefix = 'address'): array
    {
        return collect(self::baseRules())
            ->mapWithKeys(fn ($rule, $key) => [
                ($prefix ? "{$prefix}." : '') . $key => $rule,
            ])
            ->toArray();
    }

    public function rules(): array
    {
        return self::baseRules();
    }
}
