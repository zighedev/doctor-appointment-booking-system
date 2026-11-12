<?php

namespace App\Http\Requests\Admin\Specialty;

use App\Http\Requests\Admin\AdminRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class SpecialtyFilterRequest extends AdminRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', 'boolean'],
        ];
    }
}