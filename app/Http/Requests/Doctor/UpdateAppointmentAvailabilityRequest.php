<?php

namespace App\Http\Requests\Doctor;


class UpdateAppointmentAvailabilityRequest extends DoctorRequest
{
    
    /**
     * Get the validation rules for updating appointment availability.
     */
    public function rules(): array
    {
        return [
            'is_accepting_appointments' => ['required', 'boolean'],
        ];
    }
}
