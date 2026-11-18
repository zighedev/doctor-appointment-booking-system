<?php

namespace App\Http\Requests\Admin;

class UpdateStatusRequest extends AdminRequest
{
    /**
     * Get the validation rules for updating account status.
     */
    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }
    
}
