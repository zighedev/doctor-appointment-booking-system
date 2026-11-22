<?php

namespace App\Http\Requests\Doctor;

use Illuminate\Foundation\Http\FormRequest;

abstract class DoctorRequest extends FormRequest
{
    /**
     * Determine whether the authenticated user is a doctor.
     */
    public function authorize(): bool
    {
        return $this->user()?->isDoctor() ?? false;
    }
    
}