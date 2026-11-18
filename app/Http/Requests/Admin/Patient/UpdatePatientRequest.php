<?php

namespace App\Http\Requests\Admin\Patient;

use App\Http\Requests\Admin\AdminRequest;

class UpdatePatientRequest extends AdminRequest
{

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
