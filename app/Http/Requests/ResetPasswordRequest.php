<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
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
            //
            'email'    => 'required|email',
            'code'     => 'required|string|size:6',
            
            'password' => [
                'required',
                'string',
                'confirmed',   // compara password vs password_confirmation
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'El correo electrónico es obligatorio.',
            'email.email'       => 'El formato del correo no es válido.',
            'code.required'     => 'El código de verificación es obligatorio.',
            'code.size'         => 'El código debe tener exactamente 6 dígitos.',
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.min'      => 'La contraseña debe tener al menos 8 caracteres.',
            'password.mixed'    => 'La contraseña debe incluir mayúsculas y minúsculas.',
            'password.numbers'  => 'La contraseña debe incluir al menos un número.',
            'password.symbols'  => 'La contraseña debe incluir al menos un símbolo (@, #, $, %, &...).',
            'password.confirmed'=> 'Las contraseñas no coinciden.',
        ];
    }
}
