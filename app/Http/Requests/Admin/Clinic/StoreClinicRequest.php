<?php

namespace App\Http\Requests\Admin\Clinic;

use App\Http\Requests\Admin\AdminRequest;
use App\Enums\BookingConfirmationMode;
use App\Enums\PaymentPolicy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreClinicRequest extends AdminRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:clinics,phone'],
            'email' => ['nullable', 'email', 'max:255', 'unique:clinics,email'],
            'description' => ['nullable', 'string'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'timezone' => ['nullable', 'timezone'],
            'booking_confirmation_mode' => ['sometimes', Rule::enum(BookingConfirmationMode::class)],
            'payment_policy' => ['sometimes', Rule::enum(PaymentPolicy::class)],
            'reschedule_cutoff_hours' => ['sometimes', 'integer', 'between:0,168'],
            'confirmation_timeout_minutes' => ['sometimes', 'integer', 'between:5,1440'],
            'payment_timeout_minutes' => ['sometimes', 'integer', 'between:5,120'] 
        ];
    }
}
