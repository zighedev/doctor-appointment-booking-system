<?php

namespace App\Http\Requests\Admin\Doctor;

use App\Http\Requests\Admin\AdminRequest;

class DoctorFilterRequest extends AdminRequest
{

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'verified' => ['sometimes', 'required', 'boolean'],
            'accepting_appointments' => ['sometimes', 'required', 'boolean'],
            'status' => ['sometimes', 'required', 'boolean'],
            'specialty' => ['sometimes', 'required', 'string', 'exists:specialties,slug'],
            'clinic' => ['sometimes', 'required', 'integer', 'exists:clinics,id'],
        ];        
    }
}
