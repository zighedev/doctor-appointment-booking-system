<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;
use App\Enums\UserGender;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'], 
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'], 
            'phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'], 
            'password' => ['required', 'confirmed', Password::min(8)], 
            'gender' => ['nullable', new Enum(UserGender::class)], 
            'date_of_birth' => ['nullable', 'date', 'before:today']
        ];
    }
}
