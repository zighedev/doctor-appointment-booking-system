<?php

namespace App\Http\Requests\Admin\Doctor;

use App\Http\Requests\Admin\AdminRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorRequest extends AdminRequest
{

    /**
     * Get the validation rules for updating a doctor.
     */
    public function rules(): array
    {
        $doctor = $this->route('doctor');

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
