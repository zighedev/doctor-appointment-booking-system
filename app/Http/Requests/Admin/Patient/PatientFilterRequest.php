<?php

namespace App\Http\Requests\Admin\Patient;

use App\Enums\UserGender;
use App\Http\Requests\Admin\AdminRequest;
use Illuminate\Validation\Rule;

class PatientFilterRequest extends AdminRequest
{
    
    public function rules(): array
    {
        return [
            'registration_period' => [
                'sometimes', 
                'required', 
                Rule::in(['today', 'last_7_days', 'last_30_days', 'this_month'])
            ],
            'gender' => ['sometimes', 'required', Rule::enum(UserGender::class)],
            'status' => ['sometimes', 'required', 'boolean'],
        ];
    }
}