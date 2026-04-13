<?php

namespace App\Http\Requests\Settings\Countries;

use App\Models\Settings\CountryModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $country = $this->route('country');
        $countryId = $country instanceof CountryModel ? $country->getKey() : $country;

        return [
            'name' => ['sometimes', 'string', 'max:120', Rule::unique('countries', 'name')->ignore($countryId)],
            'code' => ['nullable', 'string', 'size:2', Rule::unique('countries', 'code')->ignore($countryId)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
