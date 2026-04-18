<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                'different:current_password',
                Password::min(10)->mixedCase()->numbers()->symbols(),
            ],
            'logout_other_devices' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Please enter your current password.',
            'current_password.current_password' => 'Current password is incorrect.',
            'password.different' => 'New password must be different from your current password.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
