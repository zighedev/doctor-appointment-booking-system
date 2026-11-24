<?php

namespace App\Http\Requests\Admin\DoctorSpecialty;

use App\Http\Requests\Admin\AdminRequest;

use Illuminate\Validation\Rule;

class AttachSpecialtyRequest extends AdminRequest
{
    
    public function rules(): array
    {
        $doctor = $this->route('doctor');

        return [
            'specialty_id' => [
                'required',
                'integer',
                Rule::exists('specialties', 'id')->where('is_active', true),
                Rule::unique('doctor_specialty', 'specialty_id')->where('doctor_id', $doctor->id),
            ],
        ];

    }
}
