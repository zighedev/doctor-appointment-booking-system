<?php

namespace App\Http\Requests\Admin\Patient;

use App\Enums\UserGender;
use App\Http\Requests\Admin\AdminRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StorePatientRequest extends AdminRequest
{

    public function rules(): array
    {
        return [
            // User data
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],
            'gender' => ['nullable', Rule::Enum(UserGender::class)],
            'date_of_birth' => ['nullable', 'date', 'before:today'],

            // Patient data
            'blood_type' => ['nullable', 'string', 'max:5'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
        ];
    }
}
