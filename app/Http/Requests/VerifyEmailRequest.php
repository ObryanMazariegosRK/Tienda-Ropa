<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            // size:6 garantiza que el string tenga exactamente 6 caracteres
            'code'  => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email'    => 'El formato del correo no es válido.',
            'code.required'  => 'El código de verificación es obligatorio.',
            'code.size'      => 'El código de verificación debe tener exactamente 6 caracteres.',
        ];
    }
}
