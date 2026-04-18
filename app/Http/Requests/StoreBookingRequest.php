<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use App\Models\Room;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $hasAdults = $this->filled('adults');
        $hasKids = $this->filled('kids');

        if ($hasAdults || $hasKids) {
            $adultCount = max(0, (int) $this->input('adults', 0));
            $kidCount = max(0, (int) $this->input('kids', 0));

            $this->merge([
                'guests' => max(1, $adultCount + $kidCount),
            ]);
        }

    }

    public function rules(): array
    {
        $provinceRules = ['nullable', 'string', 'max:120'];
        $availableProvinces = config('philippines.provinces', []);
        if (!empty($availableProvinces)) {
            $provinceRules[] = Rule::in($availableProvinces);
        }

        return [
            'room_id' => ['required', 'exists:rooms,room_id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after_or_equal:check_in'],
            'guests' => ['required', 'integer', 'min:1'],
            'adults' => ['nullable', 'integer', 'min:1'],
            'kids' => ['nullable', 'integer', 'min:0'],
            'first_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'street_address_line_2' => ['nullable', 'string', 'max:255'],
            'guest_city' => ['nullable', 'string', 'max:120'],
            'state_province' => $provinceRules,
            'postal_code' => ['nullable', 'string', 'max:40'],
            'contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\\-\\s]{7,30}$/'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'payment_preference' => ['nullable', 'in:cash,bank_transfer,gcash,paymaya'],
            'discount_type' => ['nullable', 'in:none,pwd,senior'],
            'discount_id' => ['nullable', 'string', 'max:80', 'required_if:discount_type,pwd,senior'],
            'discount_id_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'required_if:discount_type,pwd,senior'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_phone.regex' => 'Contact phone must contain only digits, spaces, +, (), or -.',
            'state_province.in' => 'Please select a valid province from the suggested list.',
            'discount_id_photo.required_if' => 'Upload a PWD/Senior ID photo when selecting a discount.',
            'discount_id_photo.image' => 'Discount ID upload must be an image file.',
            'discount_id_photo.max' => 'Discount ID photo must not exceed 5MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $room = Room::find($this->integer('room_id'));

            if (!$room) {
                return;
            }

            if (!$room->is_available) {
                $validator->errors()->add('room_id', 'Selected room is currently unavailable.');
            }

            if ($this->filled('guests') && $this->integer('guests') > $room->capacity) {
                $validator->errors()->add('guests', 'Guest count exceeds room capacity (max '.$room->capacity.').');
            }

            $checkIn = trim((string) $this->input('check_in', ''));
            $checkOut = trim((string) $this->input('check_out', ''));

            if ($checkIn === '' || $checkOut === '') {
                return;
            }

            try {
                $checkInDate = Carbon::parse($checkIn)->startOfDay();
                $checkOutDate = Carbon::parse($checkOut)->startOfDay();
            } catch (\Throwable) {
                return;
            }

            if ($checkOutDate->lessThanOrEqualTo($checkInDate)) {
                $validator->errors()->add('check_out', 'Nightly bookings require check-out to be at least one day after check-in.');
            }
        });
    }
}
