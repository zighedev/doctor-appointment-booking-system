<?php

namespace App\Http\Requests\Admin\Doctor;

use App\Enums\UserGender;
use App\Http\Requests\Admin\AdminRequest;
use Illuminate\Validation\Rule;

class StoreDoctorRequest extends AdminRequest
{

    /**
     * Get the validation rules for creating a doctor.
     */
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

            // Doctor data
            'license_number' => ['nullable', 'string', 'max:100', 'unique:doctors,license_number'],
            'bio' => ['nullable', 'string'],
            'years_of_experience' => ['sometimes', 'integer', 'between:0,80'],
        ];
    }
}
