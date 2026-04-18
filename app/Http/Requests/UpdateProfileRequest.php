<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        if (!$this->user()?->isCustomer()) {
            return [
                'first_name' => ['required', 'string', 'max:120'],
                'last_name' => ['required', 'string', 'max:120'],
                'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\\-\\s]{7,30}$/'],
            ];
        }

        $provinceRules = ['required', 'string', 'max:120'];
        $availableProvinces = config('philippines.provinces', []);
        if (!empty($availableProvinces)) {
            $provinceRules[] = Rule::in($availableProvinces);
        }

        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\\-\\s]{7,30}$/'],
            'address_line' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'province' => $provinceRules,
            'country' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Please enter your first name.',
            'last_name.required' => 'Please enter your last name.',
            'phone.regex' => 'Phone must contain only digits, spaces, +, (), or -.',
            'province.in' => 'Please select a valid province from the suggested list.',
        ];
    }
}
