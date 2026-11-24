<?php

namespace App\Http\Requests\Admin\DoctorClinic;

use App\Http\Requests\Admin\AdminRequest;
use Illuminate\Validation\Rule;

class AttachClinicRequest extends AdminRequest
{
    
    public function rules(): array
    {
        $doctor = $this->route('doctor');

        return [
            'clinic_id' => [
                'required',
                'integer',
                Rule::exists('clinics', 'id')->where('is_active', true),
                Rule::unique('clinic_doctor', 'clinic_id')->where('doctor_id', $doctor->id),
            ],

            'consultation_fee' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
        ];
    }

}
