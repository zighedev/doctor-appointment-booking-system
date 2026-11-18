<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

abstract class PatientRequest extends FormRequest
{
    /**
     * Determine whether the authenticated user is a patient.
     */
    public function authorize(): bool
    {
        return $this->user()?->isPatient() ?? false;
    }
    
}