<?php

namespace App\Http\Requests\Admin\Doctor;

use App\Http\Requests\Admin\AdminRequest;

class UpdateVerificationStatusRequest extends AdminRequest
{
    /**
     * Get the validation rules for updating doctor verification status. 
     */
    public function rules(): array
    { 
        return [ 
            'is_verified' => ['required', 'boolean'], 
        ]; 
    }
}
