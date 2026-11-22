<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Validation\Rule;

class UpdateMyDoctorProfileRequest extends DoctorRequest
{

    /**
     * Get the validation rules for updating doctor profile.
     */
    public function rules(): array
    {
        $doctor = $this->user()->doctor;
        
        return [
            'license_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('doctors', 'license_number')->ignore($doctor),
            ],
            'bio' => ['sometimes', 'nullable', 'string'],
            'years_of_experience' => ['sometimes', 'integer', 'between:0,80'],
        ];

    }
}
