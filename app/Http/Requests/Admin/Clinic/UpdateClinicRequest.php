<?php

namespace App\Http\Requests\Admin\Clinic;

use App\Http\Requests\Admin\AdminRequest;
use App\Enums\BookingConfirmationMode;
use App\Enums\PaymentPolicy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateClinicRequest extends AdminRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $clinic = $this->route('clinic');

        return [

            'name' => ['sometimes', 'required', 'string', 'max:200'],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
                Rule::unique('clinics', 'phone')->ignore($clinic),
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('clinics', 'email')->ignore($clinic),
            ],

            'description' => ['sometimes', 'nullable', 'string'],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'state' => ['sometimes', 'nullable', 'string', 'max:100'],
            'country' => ['sometimes', 'required', 'string', 'max:100'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'timezone' => ['sometimes', 'nullable', 'timezone'],
            'booking_confirmation_mode' => ['sometimes', Rule::enum(BookingConfirmationMode::class)],
            'payment_policy' => ['sometimes', Rule::enum(PaymentPolicy::class)],
            'reschedule_cutoff_hours' => ['sometimes', 'integer', 'between:0,168'],
            'confirmation_timeout_minutes' => ['sometimes', 'integer', 'between:5,1440'],
            'payment_timeout_minutes' => ['sometimes', 'integer', 'between:5,120']
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->hasAny([
                    'name',
                    'phone',
                    'email',
                    'description',
                    'address',
                    'city',
                    'state',
                    'country',
                    'latitude',
                    'longitude',
                    'timezone',
                    'booking_confirmation_mode',
                    'payment_policy',
                    'reschedule_cutoff_hours',
                    'confirmation_timeout_minutes',
                    'payment_timeout_minutes',
                ])) {
                    $validator->errors()->add(
                        'request',
                        'At least one field must be provided.'
                    );
                }
            },
        ];
    }
    
}
