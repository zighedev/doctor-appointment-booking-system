<?php

namespace App\Http\Requests\Admin\Specialty;

use App\Http\Requests\Admin\AdminRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSpecialtyRequest extends AdminRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
       $specialty = $this->route('specialty');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('specialties', 'name')->ignore($specialty),
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->hasAny(['name', 'description'])) {
                    $validator->errors()->add(
                        'request',
                        'At least one field must be provided.'
                    );
                }
            },
        ];
    }
    
}
