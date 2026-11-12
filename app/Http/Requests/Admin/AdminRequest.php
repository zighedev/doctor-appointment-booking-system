<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

abstract class AdminRequest extends FormRequest
{
    /**
     * Determine whether the authenticated user is an administrator.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }
    
}