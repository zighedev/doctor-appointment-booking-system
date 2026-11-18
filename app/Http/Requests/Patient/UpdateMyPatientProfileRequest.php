<?php

namespace App\Http\Requests\Patient;

use Illuminate\Contracts\Validation\ValidationRule;

class UpdateMyPatientProfileRequest extends PatientRequest
{
    

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'blood_type' => ['sometimes', 'nullable', 'max:5'],
            'emergency_contact_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'emergency_contact_relationship' => ['sometimes', 'nullable', 'string', 'max:100'],
            'address' => ['sometimes', 'nullable', 'string'],
        ];

    }
}
