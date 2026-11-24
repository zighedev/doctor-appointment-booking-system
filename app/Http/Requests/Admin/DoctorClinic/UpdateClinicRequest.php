<?php

namespace App\Http\Requests\Admin\DoctorClinic;

use App\Http\Requests\Admin\AdminRequest;

class UpdateClinicRequest extends AdminRequest
{
    
    public function rules(): array
    {
        return [
            'consultation_fee' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],

            'is_active' => [
                'sometimes',
                'required',
                'boolean',
            ],
        ];
    }

}
